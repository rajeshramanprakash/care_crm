<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OutboundCall
{
    protected $baseUrl = 'https://api.mcube.com/Restmcube-api/outbound-calls';
    protected $token;

    public function __construct()
    {
        $this->token = config('services.mcube.token');
    }

    public static function call($customerNumber, $refId = null, $user = null)
    {
        $instance = new static();
        
        // Use provided user or get from auth
        $currentUser = $user ?? Auth::user();
        
        if (!$currentUser) {
            return [
                'success' => false,
                'message' => 'User not authenticated'
            ];
        }
        
        $executiveNumber = $currentUser->mobile;

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

        return $instance->makeCall($executiveNumber, $customerNumber, $refId, $currentUser);
    }

    protected function makeCall($executiveNumber, $customerNumber, $refId = null, $user = null)
    {
        try {
            // MCube API (Commented for future use)
            /*
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
            */

            // Tata Click-to-Call API
            return $this->makeTataClickToCall($executiveNumber, $customerNumber, $refId, $user);

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

    /**
     * Make Tata Click-to-Call API request
     */
    protected function makeTataClickToCall($executiveNumber, $customerNumber, $refId = null, $user = null)
    {
        try {
            $token = config('services.tata.token', env('TATA_API_TOKEN'));
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Tata API token not configured'
                ];
            }

            // Get user's Tata agent ID
            $currentUser = $user ?? Auth::user();
            if (!$currentUser || !$currentUser->tata_agent_id || $currentUser->is_break == '1') {
                return [
                    'success' => false,
                    'message' => 'User does not have a Tata agent ID or is on break. Please create agent first or check break status.'
                ];
            }

            $requestData = [
                'agent_number' => $currentUser->mobile,
                'destination_number' => $customerNumber,
                'caller_id' => '7965088076',
                'async' => 1, 
            ];

            if ($refId) {
                $requestData['custom_identifier'] = $refId;
            }

            Log::info('Making Tata Click-to-Call request', [
                'url' => 'https://api-smartflo.tatateleservices.com/v1/click_to_call',
                'data' => $requestData
            ]);

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->post('https://api-smartflo.tatateleservices.com/v1/click_to_call', $requestData);

            $responseData = $response->json();
            Log::info('Tata Click-to-Call response', ['response' => $responseData]);

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => $responseData['message'] ?? 'Call initiated successfully'
                ];
            }

            // Handle error cases
            $errorMessage = $responseData['message'] ?? 'Failed to initiate call';
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error_type' => $responseData['message'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Tata Click-to-Call failed', [
                'error' => $e->getMessage(),
                'executive_number' => $executiveNumber,
                'customer_number' => $customerNumber
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate Tata call: ' . $e->getMessage()
            ];
        }
    }
}