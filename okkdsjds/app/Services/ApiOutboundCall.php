<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ApiOutboundCall
{
    protected $baseUrl = 'https://api.mcube.com/Restmcube-api/outbound-calls';
    protected $token;

    public function __construct()
    {
        $this->token = config('services.mcube.token');
    }

    public static function call($customerNumber, $user_id, $refId = null)
    {
        $instance = new static();


        $executiveNumber = User::find($user_id)->mobile;

        // Validate executive number
        if (empty($executiveNumber)) {
            return [
                'success' => false,
                'message' => 'Executive mobile number is not set'
            ];
        }

        // Validate customer number
        if (empty($customerNumber)) {
            return [
                'success' => false,
                'message' => 'Customer number is required'
            ];
        }

        return $instance->makeCall($executiveNumber, $customerNumber, $refId);
    }

    protected function makeCall($executiveNumber, $customerNumber, $refId = null)
    {
        try {
            $requestData = [
                'exenumber' => $executiveNumber,
                'custnumber' => $customerNumber,
                'refurl' => '1'
            ];

            if ($refId) {
                $requestData['refid'] = $refId;
            }

            Log::info('Making outbound call request', [
                'url' => $this->baseUrl,
                'data' => $requestData
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ])->post($this->baseUrl, $requestData);

            $responseData = $response->json();
            Log::info('Outbound call response', ['response' => $responseData]);

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            }

            // Handle specific error cases
            $errorMessage = $responseData['msg'] ?? 'Failed to initiate call';

            if (str_contains($errorMessage, 'Executive is not opted for Outbound Calls')) {
                $errorMessage = 'Executive is not registered for outbound calls. Please contact support.';
            } elseif (str_contains($errorMessage, 'agent is in oncall')) {
                $errorMessage = 'Executive is currently on another call. Please try again later.';
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_type' => $responseData['msg'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Outbound call failed', [
                'error' => $e->getMessage(),
                'executive_number' => $executiveNumber,
                'customer_number' => $customerNumber
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate call: ' . $e->getMessage()
            ];
        }
    }
}
