<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\OperationLead;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use App\Models\WhatsAppMessage;
use App\Models\CallLog;
use App\Models\CallDetails;
use Illuminate\Support\Facades\Log;
use App\Facades\UserAssignment;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\ExpoNotificationService;
use App\Services\ApiRelayUrlResolver;
use App\Services\MissedCallbackAgentPresence;
use App\Services\MissedCallbackSequenceService;
use App\Models\MissedCallbackSequence;

class TataService
{
    private function callTataApiThroughRelaySafe(string $method, string $targetUrl, array $options = [])
    {
        $resolvedUrl = ApiRelayUrlResolver::resolve($targetUrl);
        $isRelay = $resolvedUrl !== $targetUrl;

        $headers = $options['headers'] ?? [];
        $jsonPayload = $options['json'] ?? null;

        if ($isRelay && in_array(strtoupper($method), ['PATCH', 'DELETE'], true)) {
            $headers['X-Relay-Method'] = strtoupper($method);
            $client = new \GuzzleHttp\Client();
            $relayOptions = ['headers' => $headers];
            if ($jsonPayload !== null) {
                $relayOptions['json'] = $jsonPayload;
            }

            return $client->request('POST', $resolvedUrl, $relayOptions);
        }

        $client = new \GuzzleHttp\Client();

        return $client->request(strtoupper($method), $resolvedUrl, $options);
    }

    /**
     * Convert Tata webhook `direction` into a UI-friendly label.
     * Tata click-to-call comes as `clicktocall` which should be treated as outbound.
     */
    private function getCallDirectionLabel(array $data): string
    {
        $direction = strtolower(trim((string)($data['direction'] ?? '')));
        if ($direction === '') {
            // Fallback: if click-to-call specific fields exist
            if (!empty($data['call_to_number']) && !empty($data['caller_id_number'])) {
                return 'outbound';
            }
            return 'unknown';
        }

        if (in_array($direction, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)) {
            return 'outbound';
        }

        if ($direction === 'inbound') {
            return 'inbound';
        }

        return $direction;
    }

    public function handleCallData(array $data)
    {
        try {
            Log::info('Tata service call data received', [
                'caller_id_number' => $data['caller_id_number'] ?? 'unknown',
                'call_status' => $data['call_status'] ?? 'unknown',
                'call_id' => $data['call_id'] ?? 'unknown',
                'start_stamp' => $data['start_stamp'] ?? 'unknown'
            ]);

            $this->processMissedCallbackOutboundWebhook($data);
            
            // Validate required fields
            if (empty($data['caller_id_number'])) {
                throw new \Exception('Missing caller_id_number in webhook data');
            }

            // First, try to find and update existing call record from dialplan
            $existingCallRecord = $this->findAndUpdateExistingCallRecord($data); 
            
            // If we found and updated an existing call record, we can return early
            // as the call has been processed and updated with final status
            if ($existingCallRecord) {
                Log::info('✅ WEBHOOK: Existing call record updated successfully, skipping further processing', [
                    'call_details_id' => $existingCallRecord->id,
                    'final_status' => $data['call_status'] ?? 'unknown',
                    'call_source' => $existingCallRecord->call_source
                ]);
                
                // Update the lead/operation/job request with final call status if needed
                $this->updateRelatedRecordWithCallStatus($existingCallRecord, $data);

                // Dialplan path only persisted call_details; ensure legacy call_logs row for leads
                $this->mirrorCallLogAfterDialplanWebhook($existingCallRecord, $data);
                
                return $existingCallRecord;
            }
            // Create recording data in the required format
            $recordingData = [];
            if (!empty($data['recording_url'])) {
                $recordingData = [
                    [
                        'url' => $data['recording_url'],
                        'metadata' => json_encode(array_merge([
                            'datetime' => $data['start_stamp'],
                            'caller_agent' => ($n = $this->getAgentName($data)),
                            'agent_name' => $n,
                            'dialstatus' => $data['call_status'] ?? null,
                            'call_id' => $data['call_id'] ?? null,
                            'hangup_cause' => $data['hangup_cause'] ?? null,
                            'duration' => $data['duration'] ?? null,
                            'call_direction' => $this->getCallDirectionLabel($data),
                        ], !empty($data['call_to_number']) ? ['agent_number' => $data['call_to_number']] : []))
                    ]
                ];
            }

            // Get the executive user from agent information
            $getUser = $this->getExecutiveUser($data);
            if (!$getUser) {
                $getUser = $this->pickRandomActiveSalesUserForTataFallback();
                if (!$getUser) {
                    Log::error('❌ CRITICAL: No agent from webhook and no active sales user for fallback', [
                        'call_id' => $data['call_id'] ?? 'unknown',
                        'caller_id_number' => $data['caller_id_number'] ?? 'unknown',
                    ]);
                    throw new \Exception('No active sales executive available for call webhook fallback');
                }
                Log::warning('⚠️ FALLBACK: No agent from webhook; assigned random active sales user (duty on, break off)', [
                    'call_id' => $data['call_id'] ?? 'unknown',
                    'caller_id_number' => $data['caller_id_number'] ?? 'unknown',
                    'agent_name' => $this->getAgentName($data),
                    'fallback_user_id' => $getUser->id,
                    'fallback_user_name' => $getUser->name,
                    'fallback_user_mobile' => $getUser->mobile,
                ]);
            } else {
                Log::info('✅ Agent user found successfully', [
                    'user_id' => $getUser->id,
                    'user_name' => $getUser->name,
                    'user_mobile' => $getUser->mobile
                ]);
            }

            app(MissedCallbackAgentPresence::class)->refreshFromTataWebhook($getUser, $data);

            // Get agent name for all calls
            $agentName = $this->getAgentName($data);

            // Customer number for lookup: inbound = caller_id_number, outbound/clicktocall = call_to_number
            $customerNumber = $this->getCustomerPhoneNumberForLookup($data);
            $callerIdNumber = $customerNumber;

            // Check in priority order: 1. JobRequest, 2. OperationLead, 3. Lead
            
            // FIRST: Check if JobRequest exists with same contact number
            $jobRequest = $this->findExistingJobRequestByPhoneNumber($customerNumber);
            if ($jobRequest) {
                Log::info('✅ WEBHOOK: Processing JobRequest (PRIORITY 1)', [
                    'job_request_id' => $jobRequest->id,
                    'caller_number' => $customerNumber
                ]);
                
                // Update JobRequest with call data
                $this->updateJobRequestWithCallStatus($jobRequest, $data);

                $this->saveCallDetailsForJobRequest($jobRequest, $data, $agentName, $getUser, $customerNumber);
                
                // Check if this JobRequest has an active deployment
                $activeDeployment = \App\Models\OperationDeploymentDetails::where('freelance_staff_id', $jobRequest->id)
                    ->where('deployment_status', 'In Progress')
                    ->first();
                
                if ($activeDeployment) {
                    $operationLead = \App\Models\OperationLead::find($activeDeployment->operation_lead_id);
                    if ($operationLead) {
                        $this->updateOperationLeadActiveDeploymentRecording($operationLead, $data, $agentName, $getUser, $jobRequest);
                    }
                }
                
                return $jobRequest;
            }
            
            // SECOND: Check if OperationLead exists with same contact number
            $operationLead = $this->findExistingOperationLeadByPhoneNumber($customerNumber);
            if ($operationLead) {
                Log::info('✅ WEBHOOK: Processing OperationLead (PRIORITY 2)', [
                    'operation_lead_id' => $operationLead->id,
                    'caller_number' => $customerNumber
                ]);
                
                // Update OperationLead with call data
                $this->updateOperationLeadWithCallStatus($operationLead, $data);

                $this->saveCallDetailsForOperationLead($operationLead, $data, $agentName, $getUser, $customerNumber);

                return $operationLead;
            }

            // THIRD: Check if normal Lead exists with this contact number
            $existingLead = $this->findExistingLeadByPhoneNumber($customerNumber);

            if ($existingLead) {
                Log::info('✅ WEBHOOK: Processing Lead (PRIORITY 3)', [
                    'lead_id' => $existingLead->id,
                    'caller_number' => $customerNumber
                ]);
                
                // Assign executive if not assigned
                if (!$existingLead->executive) {
                    $existingLead->executive = $getUser->id;
                    $existingLead->save();
                }
                
                // Update Lead with call data
                $this->updateLeadWithCallStatus($existingLead, $data);
                
                $this->sendCallNotification($existingLead, $data, $agentName);
                $this->saveCallLog($existingLead, $data, $agentName, $getUser, $customerNumber);

                Log::info('✅ Lead processing completed (PRIORITY 3)', [
                    'lead_id' => $existingLead->id,
                    'call_status' => $data['call_status'] ?? 'unknown',
                    'customer_number' => $customerNumber
                ]);

                return $existingLead;
            } else {
                // No existing lead found - create a new lead with call data
                Log::info('No existing lead found, creating new lead for Tata webhook', [
                    'customer_number' => $customerNumber,
                    'call_id' => $data['call_id'] ?? null,
                    'agent_name' => $agentName
                ]);

                // Prepare recording data for new lead
                $recordingData = [];
                if (!empty($data['recording_url'])) {
                    $recordingData[] = [
                        'url' => $data['recording_url'],
                        'metadata' => json_encode([
                            'datetime' => $data['start_stamp'],
                            'caller_agent' => $agentName,
                            'dialstatus' => $data['call_status'] ?? null,
                            'call_id' => $data['call_id'] ?? null,
                            'hangup_cause' => $data['hangup_cause'] ?? null,
                            'duration' => $data['duration'] ?? null,
                            'agent_name' => $agentName
                            ,'call_direction' => $this->getCallDirectionLabel($data)
                        ])
                    ];
                }

                $mcb = app(MissedCallbackSequenceService::class);
                $initialStatus = $mcb->isInboundMissedTrigger($data) ? 'no response' : 'follow-up';

                // Create new lead with call data (missed inbound → no response; callback flow starts only here)
                $newLead = Lead::create([
                    'date' => now(),
                    'executive' => $getUser->id,
                    'contact_no' => $customerNumber,
                    'lead_source' => 'call',
                    'status' => $initialStatus,
                    'stage' => 'active',
                    'recording_url' => !empty($recordingData) ? json_encode($recordingData) : null,
                    'last_call_status' => $data['call_status'] ?? null
                ]);

                // Send notification for the new lead
                $this->sendCallNotification($newLead, $data, $agentName);

                // Save call log (pass customer number so outbound calls store correct contact)
                $this->saveCallLog($newLead, $data, $agentName, $getUser, $customerNumber);
                $this->maybeStartMissedCallbackSequenceForLead($newLead, $data, $getUser);

                Log::info('✅ New lead created and processed', [
                    'lead_id' => $newLead->id,
                    'call_status' => $data['call_status'] ?? 'unknown',
                    'customer_number' => $customerNumber
                ]);

                return $newLead;
            }

        } catch (\Exception $e) {
            Log::error('Tata service error: ' . $e->getMessage(), [
                'call_id' => $data['call_id'] ?? null,
                'caller_id_number' => $data['caller_id_number'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Idempotent insert into call_logs for sales leads (dialplan + webhook path skips saveCallLog).
     */
    private function ensureLeadCallLogMirror(Lead $lead, array $data, User $executiveUser, string $agentName, string $callerNumber): void
    {
        try {
            $callId = isset($data['call_id']) ? trim((string) $data['call_id']) : '';

            $callReceivedDatetime = ! empty($data['start_stamp'])
                ? Carbon::parse($data['start_stamp'])
                : now();
            $recordingUrl = ! empty($data['recording_url']) ? $data['recording_url'] : null;
            $agentNumber = $this->getAgentNumber($data);

            $payload = [
                'lead_id' => $lead->id,
                'tata_call_id' => $callId !== '' ? $callId : null,
                'executive_id' => $executiveUser->id,
                'caller_id_number' => $callerNumber,
                'agent_number' => $agentNumber,
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
                'recording_url' => $recordingUrl,
                'call_received_datetime' => $callReceivedDatetime,
                'customer_name' => $lead->customer_name ?? null,
                'lead_code' => $lead->formatted_id ?? '#'.$lead->id,
                'is_processed' => false,
            ];

            if ($callId !== '') {
                CallLog::firstOrCreate(
                    ['tata_call_id' => $callId],
                    $payload
                );
            } else {
                CallLog::create($payload);
            }

            Log::info('Call log mirror: call_logs row ensured for lead', [
                'lead_id' => $lead->id,
                'call_id' => $data['call_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('ensureLeadCallLogMirror: '.$e->getMessage(), [
                'lead_id' => $lead->id ?? null,
            ]);
        }
    }

    /**
     * After dialplan CallDetails is finalized by webhook, mirror to call_logs when call_for = lead.
     */
    private function mirrorCallLogAfterDialplanWebhook(CallDetails $record, array $data): void
    {
        try {
            if ($record->call_for !== 'lead' || empty($record->lead_id)) {
                return;
            }

            $lead = Lead::find($record->lead_id);
            if (! $lead) {
                return;
            }

            $exec = null;
            if (! empty($record->executive_id)) {
                $exec = User::find($record->executive_id);
            }
            if (! $exec && ! empty($lead->executive)) {
                $exec = User::find($lead->executive);
            }
            if (! $exec) {
                $exec = $this->pickRandomActiveSalesUserForTataFallback();
            }
            if (! $exec) {
                Log::warning('mirrorCallLogAfterDialplanWebhook: no executive for mirror', [
                    'call_details_id' => $record->id,
                    'lead_id' => $lead->id,
                ]);

                return;
            }

            $agentName = $this->getAgentName($data);
            $callerNumber = $record->caller_id_number ?: $this->getCustomerPhoneNumberForLookup($data);
            $this->ensureLeadCallLogMirror($lead, $data, $exec, $agentName, $callerNumber);
        } catch (\Exception $e) {
            Log::error('mirrorCallLogAfterDialplanWebhook: '.$e->getMessage(), [
                'call_details_id' => $record->id ?? null,
            ]);
        }
    }

    private function getAgentName(array $data)
    {
        if (!empty($data['answered_agent_name'])) {
            return $data['answered_agent_name'];
        }

        if (!empty($data['answered_agent']) && is_array($data['answered_agent'])) {
            $agent = $data['answered_agent'];
            if (!empty($agent['name'])) {
                return $agent['name'];
            }
            if (!empty($agent['agent_number'])) {
                $user = $this->findUserByPhoneNumber($agent['agent_number']);
                if ($user) {
                    return trim($user->f_name . ' ' . ($user->l_name ?? ''));
                }
            }
        }

        if (!empty($data['missed_agent']) && is_array($data['missed_agent'])) {
            $agent = $data['missed_agent'][0];
            if (!empty($agent['name'])) {
                return $agent['name'];
            }
            if (!empty($agent['agent_number'])) {
                $user = $this->findUserByPhoneNumber($agent['agent_number']);
                if ($user) {
                    return trim($user->f_name . ' ' . ($user->l_name ?? ''));
                }
            }
        }

        if (!empty($data['call_flow']) && is_array($data['call_flow'])) {
            foreach ($data['call_flow'] as $flow) {
                if (is_array($flow) && isset($flow['type']) && $flow['type'] === 'Agent') {
                    if (!empty($flow['name'])) {
                        return $flow['name'];
                    }
                    if (!empty($flow['num'])) {
                        $user = $this->findUserByPhoneNumber($flow['num']);
                        if ($user) {
                            return trim($user->f_name . ' ' . ($user->l_name ?? ''));
                        }
                    }
                }
            }
        }

        if (!empty($data['answered_agent_number'])) {
            $user = $this->findUserByPhoneNumber($data['answered_agent_number']);
            if ($user) {
                return trim($user->f_name . ' ' . ($user->l_name ?? ''));
            }
        }

        if (!empty($data['call_to_number'])) {
            $user = $this->findUserByPhoneNumber($data['call_to_number']);
            if ($user) {
                return trim($user->f_name . ' ' . ($user->l_name ?? ''));
            }

            return $data['call_to_number'];
        }

        return 'Unknown Agent';
    }

    private function getExecutiveUser(array $data)
    {
        Log::info('🔍 Starting agent lookup process', [
            'call_id' => $data['call_id'] ?? 'unknown',
            'caller_id_number' => $data['caller_id_number'] ?? 'unknown'
        ]);

        // First try to find user by answered_agent if available
        if (!empty($data['answered_agent']) && is_array($data['answered_agent'])) {
            $agent = $data['answered_agent'];
            if (!empty($agent['agent_number'])) {
                Log::info('🔍 Checking answered_agent', [
                    'agent_number' => $agent['agent_number'],
                    'agent_name' => $agent['name'] ?? 'unknown'
                ]);
                $user = $this->findUserByPhoneNumber($agent['agent_number']);
                if ($user) {
                    Log::info('✅ Found user via answered_agent', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_mobile' => $user->mobile
                    ]);
                    return $user;
                } else {
                    Log::warning('❌ No user found for answered_agent', [
                        'agent_number' => $agent['agent_number']
                    ]);
                }
            }
        }

        // Try to find user by agent number from missed_agent
        if (!empty($data['missed_agent']) && is_array($data['missed_agent'])) {
            $agent = $data['missed_agent'][0];
            if (!empty($agent['agent_number'])) {
                Log::info('🔍 Checking missed_agent', [
                    'agent_number' => $agent['agent_number'],
                    'agent_name' => $agent['name'] ?? 'unknown'
                ]);
                $user = $this->findUserByPhoneNumber($agent['agent_number']);
                if ($user) {
                    Log::info('✅ Found user via missed_agent', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_mobile' => $user->mobile
                    ]);
                    return $user;
                } else {
                    Log::warning('❌ No user found for missed_agent', [
                        'agent_number' => $agent['agent_number']
                    ]);
                }
            }
        }

        // Try to find user by agent number from call_flow
        if (!empty($data['call_flow']) && is_array($data['call_flow'])) {
            Log::info('🔍 Checking call_flow for agent numbers', [
                'call_flow_count' => count($data['call_flow'])
            ]);
            
            foreach ($data['call_flow'] as $index => $flow) {
                if (is_array($flow) && isset($flow['type']) && $flow['type'] === 'Agent' && !empty($flow['num'])) {
                    Log::info('🔍 Checking call_flow agent', [
                        'index' => $index,
                        'agent_number' => $flow['num'],
                        'agent_name' => $flow['name'] ?? 'unknown',
                        'dial_status' => $flow['dialst'] ?? 'unknown'
                    ]);
                    
                    $user = $this->findUserByPhoneNumber($flow['num']);
                    if ($user) {
                        Log::info('✅ Found user via call_flow', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'user_mobile' => $user->mobile,
                            'agent_number' => $flow['num']
                        ]);
                        return $user;
                    } else {
                        Log::warning('❌ No user found for call_flow agent', [
                            'agent_number' => $flow['num'],
                            'agent_name' => $flow['name'] ?? 'unknown'
                        ]);
                    }
                }
            }
        }

        // Try to find user by answered_agent_number if available
        if (!empty($data['answered_agent_number'])) {
            Log::info('🔍 Checking answered_agent_number', [
                'answered_agent_number' => $data['answered_agent_number']
            ]);
            $user = $this->findUserByPhoneNumber($data['answered_agent_number']);
            if ($user) {
                Log::info('✅ Found user via answered_agent_number', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_mobile' => $user->mobile
                ]);
                return $user;
            } else {
                Log::warning('❌ No user found for answered_agent_number', [
                    'answered_agent_number' => $data['answered_agent_number']
                ]);
            }
        }

        Log::error('❌ No agent found from webhook data; will try name match then random active sales user', [
            'call_id' => $data['call_id'] ?? 'unknown',
            'caller_id_number' => $data['caller_id_number'] ?? 'unknown',
            'available_data' => [
                'answered_agent' => $data['answered_agent'] ?? null,
                'missed_agent' => $data['missed_agent'] ?? null,
                'call_flow' => $data['call_flow'] ?? null,
                'answered_agent_number' => $data['answered_agent_number'] ?? null
            ]
        ]);

        // Debug: Log all users with mobile numbers for troubleshooting
        $this->logAllUsersWithMobileNumbers();

        // Try to find user by name as last resort
        $agentName = $this->getAgentName($data);
        if ($agentName && $agentName !== 'Unknown Agent') {
            $userByName = $this->findUserByName($agentName);
            if ($userByName) {
                Log::info('✅ Found user by name as fallback', [
                    'user_id' => $userByName->id,
                    'user_name' => $userByName->name,
                    'agent_name' => $agentName
                ]);
                return $userByName;
            }
        }

        return null;
    }

    /**
     * When Tata webhook has no resolvable agent, pick any sales user (role_id 2) on duty — not admin.
     */
    private function pickRandomActiveSalesUserForTataFallback(): ?User
    {
        return User::whereRaw('FIND_IN_SET(?, role_id)', [2])
            ->where('is_active', 1)
            ->where('is_break', 0)
            ->inRandomOrder()
            ->first();
    }

    /**
     * Normalize phone number to ensure consistent format with 91 prefix
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return $phoneNumber;
        }

        // Remove any non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Remove + if present
        $cleaned = str_replace('+', '', $cleaned);
        
        // If it doesn't start with 91, add it
        if (!str_starts_with($cleaned, '91')) {
            $cleaned = '91' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Find user by phone number with multiple format attempts
     */
    private function findUserByPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            Log::warning('🔍 findUserByPhoneNumber: Empty phone number provided');
            return null;
        }

        Log::info('🔍 findUserByPhoneNumber: Starting search', [
            'original_phone' => $phoneNumber
        ]);

        // Clean the phone number
        $cleanNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        $cleanNumber = str_replace('+', '', $cleanNumber);
        
        Log::info('🔍 findUserByPhoneNumber: Cleaned number', [
            'cleaned_number' => $cleanNumber
        ]);
        
        // Try different formats
        $formats = [
            $cleanNumber,                    // As received
            '91' . $cleanNumber,            // With 91 prefix
            ltrim($cleanNumber, '91'),      // Without 91 prefix
        ];
        
        // If it already has 91, also try without it
        if (str_starts_with($cleanNumber, '91')) {
            $formats[] = substr($cleanNumber, 2);
        }
        
        // Handle Indian mobile numbers that start with 0
        if (str_starts_with($cleanNumber, '0')) {
            $formats[] = substr($cleanNumber, 1); // Remove leading 0
            $formats[] = '91' . substr($cleanNumber, 1); // Remove leading 0 and add 91
        }
        
        // Handle numbers that might be stored without country code
        if (strlen($cleanNumber) == 10 && !str_starts_with($cleanNumber, '91')) {
            $formats[] = '91' . $cleanNumber; // Add 91 prefix for 10-digit numbers
        }
        
        // Handle numbers that might be stored with +91
        if (str_starts_with($cleanNumber, '91')) {
            $formats[] = '+' . $cleanNumber; // Add + prefix
        }
        
        // Remove duplicates
        $formats = array_unique($formats);
        
        Log::info('🔍 findUserByPhoneNumber: Trying formats', [
            'formats' => $formats
        ]);
        
        foreach ($formats as $index => $format) {
            Log::info('🔍 findUserByPhoneNumber: Trying format', [
                'index' => $index,
                'format' => $format
            ]);
            
            $user = User::where('mobile', $format)->first();
            if ($user) {
                Log::info('✅ findUserByPhoneNumber: User found', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_mobile' => $user->mobile,
                    'matched_format' => $format
                ]);
                return $user;
            } else {
                Log::info('❌ findUserByPhoneNumber: No user found for format', [
                    'format' => $format
                ]);
            }
        }
        
        Log::warning('❌ findUserByPhoneNumber: No user found for any format', [
            'original_phone' => $phoneNumber,
            'tried_formats' => $formats
        ]);
        
        return null;
    }

    private function sendCallNotification($lead, array $data, $agentName)
    {
        try {
            // Get the executive assigned to this lead
            $executive = User::find($lead->executive);
            if (!$executive) {
                return;
            }

            // Prepare notification based on call status
            $callStatus = $data['call_status'] ?? 'unknown';
            $title = '';
            $body = '';

            switch ($callStatus) {
                case 'missed':
                    $title = 'Missed Call Alert';
                    $body = "Missed call from {$data['caller_id_number']}";
                    break;
                case 'answered':
                    $title = 'Call Answered';
                    $body = "Call answered from {$data['caller_id_number']}";
                    break;
                case 'busy':
                    $title = 'Call Busy';
                    $body = "Call busy from {$data['caller_id_number']}";
                    break;
                case 'no-answer':
                    $title = 'No Answer';
                    $body = "No answer from {$data['caller_id_number']}";
                    break;
                default:
                    $title = 'Call Update';
                    $body = "Call {$callStatus} from {$data['caller_id_number']}";
                    break;
            }

            $notificationData = [
                'title' => $title,
                'body' => $body,
                'data' => [
                    'lead_id' => $lead->id,
                    'contact_no' => $data['caller_id_number'],
                    'call_time' => $data['start_stamp'],
                    'call_status' => $callStatus,
                    'agent_name' => $agentName,
                    'duration' => $data['duration'] ?? null
                ]
            ];

            // Send push notification
            if ($executive->expo_token) {
                $expoService = new ExpoNotificationService();
                $expoService->send(
                    [$executive->id], 
                    $notificationData['title'], 
                    $notificationData['body']
                );
            }

            // Log the notification
            Log::info('Call notification sent', [
                'lead_id' => $lead->id,
                'executive_id' => $executive->id,
                'contact_no' => $data['caller_id_number'],
                'call_status' => $callStatus,
                'agent_name' => $agentName
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send call notification: ' . $e->getMessage());
        }
    }

    private function resolveCallTypeFromTataPayload(array $data): string
    {
        $dir = strtolower((string) ($data['direction'] ?? ''));
        if (in_array($dir, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)) {
            return 'outbound';
        }
        if (! empty($data['call_type'])) {
            return strtolower((string) $data['call_type']);
        }

        return 'inbound';
    }

    /**
     * True if we already stored this Tata call_id on a CallDetails row (duplicate webhook).
     */
    private function callDetailsExistsForTataCallId(string $callId): bool
    {
        $query = CallDetails::query();

        return CallDetails::applyWhereRawDataCallIdEquals($query, $callId)->exists();
    }

    private function saveCallLog($lead, array $data, $agentName, $executiveUser, $customerNumberForRecord = null)
    {
        try {
            // For outbound/clicktocall use customer number (lead contact) for lookup and storage
            $callerNumber = $customerNumberForRecord ?? $data['caller_id_number'];

            // One row per Tata call_id only (never skip because same lead + customer had another call).
            $callId = isset($data['call_id']) ? (string) $data['call_id'] : '';
            if ($callId !== '' && $this->callDetailsExistsForTataCallId($callId)) {
                Log::info('⚠️ CallDetails already exists for this call_id; skipping duplicate CallDetails, mirroring call_logs if needed', [
                    'call_id' => $callId,
                    'caller_number' => $callerNumber,
                    'lead_id' => $lead->id,
                ]);
                $this->ensureLeadCallLogMirror($lead, $data, $executiveUser, $agentName, $callerNumber);

                return;
            }
            
            // Get agent number from the data
            $agentNumber = $this->getAgentNumber($data);
            
            // Get customer name from lead  
            $customerName = $lead->customer_name ?? null;
            
            // Get lead code (formatted ID)
            $leadCode = $lead->formatted_id ?? '#' . $lead->id;
            
            // Parse call received datetime
            $callReceivedDatetime = isset($data['start_stamp']) 
                ? Carbon::parse($data['start_stamp']) 
                : now();

            // Calculate call duration if available
            $callDuration = null;
            if (isset($data['duration'])) {
                $callDuration = (int) $data['duration'];
            } elseif (isset($data['end_stamp']) && isset($data['start_stamp'])) {
                $startTime = Carbon::parse($data['start_stamp']);
                $endTime = Carbon::parse($data['end_stamp']);
                $callDuration = $endTime->diffInSeconds($startTime);
            }

            $callType = $this->resolveCallTypeFromTataPayload($data);

            $tataCallId = isset($data['call_id']) ? trim((string) $data['call_id']) : '';

            // Save to original CallLog table (for backward compatibility)
            CallLog::create([
                'lead_id' => $lead->id,
                'tata_call_id' => $tataCallId !== '' ? $tataCallId : null,
                'executive_id' => $executiveUser->id,
                'caller_id_number' => $callerNumber,
                'agent_number' => $agentNumber,
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
                'recording_url' => $data['recording_url'] ?? null,
                'call_received_datetime' => $callReceivedDatetime,
                'customer_name' => $customerName,
                'lead_code' => $leadCode,
                'is_processed' => false
            ]);

            // Save to new CallDetails table with enhanced data
            $callDetails = CallDetails::create([
                'lead_id' => $lead->id,
                'call_for' => 'lead',
                'executive_id' => $executiveUser->id,
                'caller_id_number' => $callerNumber,
                'agent_number' => $agentNumber,
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
                'recording_url' => $data['recording_url'] ?? null,
                'call_received_datetime' => $callReceivedDatetime,
                'customer_name' => $customerName,
                'lead_code' => $leadCode,
                'is_processed' => false,
                'call_duration' => $callDuration,
                'call_type' => $callType,
                'call_source' => 'tata',
                'raw_data' => $data // Store complete raw data for future reference
            ]);

            Log::info('Call log saved successfully to both tables', [
                'lead_id' => $lead->id,
                'call_details_id' => $callDetails->id,
                'agent_number' => $agentNumber,
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
                'call_duration' => $callDuration,
                'caller_id_number' => $callerNumber,
                'call_type' => $callType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save call log: ' . $e->getMessage(), [
                'lead_id' => $lead->id ?? 'unknown',
                'caller_id_number' => $callerNumber ?? $data['caller_id_number'] ?? 'unknown',
                'call_status' => $data['call_status'] ?? 'unknown',
                'agent_name' => $agentName ?? 'unknown',
                'executive_user_id' => $executiveUser->id ?? 'unknown',
                'error_details' => $e->getTraceAsString()
            ]);
        }
    }

    private function getAgentNumber(array $data)
    {
        // First try to get agent number from answered_agent if available
        if (!empty($data['answered_agent']) && is_array($data['answered_agent'])) {
            $agent = $data['answered_agent'];
            if (!empty($agent['agent_number'])) {
                return $this->normalizePhoneNumber($agent['agent_number']);
            }
        }

        // Try to get agent number from missed_agent
        if (!empty($data['missed_agent']) && is_array($data['missed_agent'])) {
            $agent = $data['missed_agent'][0];
            if (!empty($agent['agent_number'])) {
                return $this->normalizePhoneNumber($agent['agent_number']);
            }
        }

        // Try to get agent number from call_flow
        if (!empty($data['call_flow']) && is_array($data['call_flow'])) {
            foreach ($data['call_flow'] as $flow) {
                if (is_array($flow) && isset($flow['type']) && $flow['type'] === 'Agent' && !empty($flow['num'])) {
                    return $this->normalizePhoneNumber($flow['num']);
                }
            }
        }

        // Try to get agent number from answered_agent_number if available
        if (!empty($data['answered_agent_number'])) {
            return $this->normalizePhoneNumber($data['answered_agent_number']);
        }

        return 'Unknown';
    }

    /**
     * Get customer phone number for lead/record lookup.
     * For inbound: customer called us = caller_id_number.
     * For outbound/clicktocall: we called customer = call_to_number.
     */
    private function getCustomerPhoneNumberForLookup(array $data)
    {
        $direction = strtolower(trim((string) ($data['direction'] ?? 'inbound')));
        if (in_array($direction, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)
            && ! empty($data['call_to_number'])) {
            return $this->normalizePhoneNumber($data['call_to_number']);
        }

        return $this->normalizePhoneNumber($data['caller_id_number']);
    }

    /**
     * Generate a strong password that meets Tata API requirements
     * Requirements: 1 Upper Case, 1 Lower Case, 1 Special Character, minimum 8 characters
     */
    private function generateStrongPassword($email)
    {
        // Create a password based on email but meeting all requirements
        $emailParts = explode('@', $email);
        $username = $emailParts[0];
        
        // Ensure minimum 8 characters with all requirements
        $password = ucfirst($username) . '123!'; // Upper case, lower case, numbers, special char
        
        // If still too short, add more characters
        if (strlen($password) < 8) {
            $password .= 'Abc!';
        }

        Log::info('Generated password: ' . $password);
        
        return $password;
    }

    /**
     * Get available roles from Tata API (helper method for debugging)
     */
    public function getAvailableRoles()
    {
        try {
            $token = config('services.tata.token', env('TATA_API_TOKEN'));
            
            if (!$token) {
                throw new \Exception('Tata API token not configured');
            }

            $response = $this->callTataApiThroughRelaySafe('GET', 'https://api-smartflo.tatateleservices.com/v1/roles', [
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Available Tata roles', ['roles' => $responseData]);
            
            return $responseData;

        } catch (\Exception $e) {
            Log::error('Failed to get Tata roles', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create agent in Tata TeleServices system
     */
    public function createAgent(User $user)
    {
        try {
            // Prepare the request data - using only required fields first
            $requestData = [
                'create_agent' => true,
                'status' => true,
                'block_web_login' => true,
                'login_based_calling' => false,
                'name' => $user->f_name . ' ' . $user->l_name,
                'number' => $user->mobile,
                'email' => $user->email,
                'login_id' => $user->email,
                'user_role' => 87232, // Commented out - will add only if valid role is configured
                'password' => $this->generateStrongPassword($user->email), // Generate a strong password
                'route_call_through' => 0, // ONLY_AGENT
                'assign_extension' => false
            ];            
            // Add caller_id as it's required
            $requestData['caller_id'] = [1560602];

            // Only add caller_id if we have valid caller IDs
            // You may need to configure this based on your Tata account
            // $validCallerIds = config('services.tata.caller_ids', []);
            // if (!empty($validCallerIds) && !in_array('', $validCallerIds)) {
            //     $requestData['caller_id'] = array_map('intval', $validCallerIds);
            // }
            
            // For now, let's try without caller_id to see if that's the issue
            // $requestData['caller_id'] = []; // Uncomment this line if needed

            // Get the authorization token from config or environment
            $token = config('services.tata.token', env('TATA_API_TOKEN'));
            
            if (!$token) {
                throw new \Exception('Tata API token not configured');
            }

            // Log the request data for debugging
            Log::info('Creating Tata agent', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'request_data' => $requestData
            ]);

            $response = $this->callTataApiThroughRelaySafe('POST', 'https://api-smartflo.tatateleservices.com/v1/user', [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $requestData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Tata agent created successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'response' => $responseData
            ]);

            return [
                'success' => true,
                'data' => $responseData,
                'message' => 'Agent created successfully in Tata system'
            ];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            $errorData = json_decode($responseBody, true);
            
            Log::error('Tata API client error', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);

            $errorMessage = 'API Error: ' . ($errorData['message'] ?? 'Unknown error');
            if (isset($errorData['errors'])) {
                $errorMessage .= ' - Errors: ' . json_encode($errorData['errors']);
            }

            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Tata agent', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create agent: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get existing agents from Tata TeleServices system
     */
    public function getAgents()
    {
        try {
            $token = config('services.tata.token', env('TATA_API_TOKEN'));
            
            if (!$token) {
                throw new \Exception('Tata API token not configured');
            }

            $response = $this->callTataApiThroughRelaySafe('GET', 'https://api-smartflo.tatateleservices.com/v1/user', [
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Retrieved Tata agents', ['response' => $responseData]);
            
            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get Tata agents', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to get agents: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find existing agent by phone number
     */
    public function findAgentByPhone($phoneNumber)
    {
        try {
            $result = $this->getAgents();
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }

            $agents = $result['data']['data'] ?? [];
            
            foreach ($agents as $agent) {
                if (isset($agent['number']) && $agent['number'] == $phoneNumber) {
                    return [
                        'success' => true,
                        'data' => $agent,
                        'message' => 'Agent found'
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Agent not found with this phone number'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to find agent by phone', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to find agent: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update agent in Tata TeleServices system
     */
    public function updateAgent(User $user, $tataAgentId, $customData = null)
    {
        try {
            // Prepare the request data for updating agent
            if ($customData) {
                // Use custom data if provided (for break status updates)
                $requestData = $customData;
            } else {
                // Default data for normal agent activation
                $requestData = [
                    'block_agent' => false,
                    'block_web_login' => false,
                    'login_based_calling' => false,
                    'disable_agent' => false
                ];
            }

            // Get the authorization token from config or environment
            $token = config('services.tata.token', env('TATA_API_TOKEN'));
            
            if (!$token) {
                throw new \Exception('Tata API token not configured');
            }

            // Log the request data for debugging
            Log::info('Updating Tata agent', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tata_agent_id' => $tataAgentId,
                'request_data' => $requestData
            ]);

            $response = $this->callTataApiThroughRelaySafe('PATCH', "https://api-smartflo.tatateleservices.com/v1/user/{$tataAgentId}", [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $requestData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Tata agent updated successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tata_agent_id' => $tataAgentId,
                'response' => $responseData
            ]);

            return [
                'success' => true,
                'data' => $responseData,
                'message' => 'Agent updated successfully in Tata system'
            ];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            $errorData = json_decode($responseBody, true);
            
            Log::error('Tata API client error during update', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tata_agent_id' => $tataAgentId,
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);

            $errorMessage = 'API Error: ' . ($errorData['message'] ?? 'Unknown error');
            if (isset($errorData['errors'])) {
                $errorMessage .= ' - Errors: ' . json_encode($errorData['errors']);
            }

            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update Tata agent', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tata_agent_id' => $tataAgentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update agent: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete agent from Tata TeleServices system
     */
    public function deleteAgent($tataAgentId)
    {
        try {
            // Get the authorization token from config or environment
            $token = config('services.tata.token', env('TATA_API_TOKEN'));
            
            if (!$token) {
                throw new \Exception('Tata API token not configured');
            }

            // Log the request for debugging
            Log::info('Deleting Tata agent', [
                'tata_agent_id' => $tataAgentId
            ]);

            $response = $this->callTataApiThroughRelaySafe('DELETE', "https://api-smartflo.tatateleservices.com/v1/user/{$tataAgentId}", [
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Tata agent deleted successfully', [
                'tata_agent_id' => $tataAgentId,
                'response' => $responseData
            ]);

            return [
                'success' => true,
                'data' => $responseData,
                'message' => 'Agent deleted successfully from Tata system'
            ];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            $errorData = json_decode($responseBody, true);
            
            Log::error('Tata API client error during delete', [
                'tata_agent_id' => $tataAgentId,
                'status_code' => $response->getStatusCode(),
                'response_body' => $responseBody
            ]);

            $errorMessage = 'API Error: ' . ($errorData['message'] ?? 'Unknown error');
            if (isset($errorData['errors'])) {
                $errorMessage .= ' - Errors: ' . json_encode($errorData['errors']);
            }

            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete Tata agent', [
                'tata_agent_id' => $tataAgentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete agent: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find user by name (fallback method)
     */
    private function findUserByName($agentName)
    {
        try {
            Log::info('🔍 findUserByName: Starting name search', [
                'agent_name' => $agentName
            ]);

            // Clean the agent name (remove + and other special characters)
            $cleanName = str_replace(['+', '-', '_'], ' ', $agentName);
            $cleanName = trim($cleanName);
            
            Log::info('🔍 findUserByName: Cleaned name', [
                'clean_name' => $cleanName
            ]);

            // Try exact match first
            $user = User::whereRaw("CONCAT(f_name, ' ', l_name) = ?", [$cleanName])->first();
            if ($user) {
                Log::info('✅ findUserByName: Exact match found', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'matched_name' => $cleanName
                ]);
                return $user;
            }

            // Try partial match
            $nameParts = explode(' ', $cleanName);
            if (count($nameParts) >= 2) {
                $firstName = $nameParts[0];
                $lastName = implode(' ', array_slice($nameParts, 1));
                
                $user = User::where('f_name', 'LIKE', "%{$firstName}%")
                           ->where('l_name', 'LIKE', "%{$lastName}%")
                           ->first();
                           
                if ($user) {
                    Log::info('✅ findUserByName: Partial match found', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ]);
                    return $user;
                }
            }

            // Try first name only
            if (!empty($nameParts[0])) {
                $user = User::where('f_name', 'LIKE', "%{$nameParts[0]}%")->first();
                if ($user) {
                    Log::info('✅ findUserByName: First name match found', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'first_name' => $nameParts[0]
                    ]);
                    return $user;
                }
            }

            Log::warning('❌ findUserByName: No user found by name', [
                'agent_name' => $agentName,
                'clean_name' => $cleanName
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ findUserByName: Error during name search', [
                'agent_name' => $agentName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find existing lead by phone number with multiple format attempts
     */
    private function findExistingLeadByPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return null;
        }

        Log::info('🔍 findExistingLeadByPhoneNumber: Starting search', [
            'original_phone' => $phoneNumber
        ]);

        // Generate all possible formats for the phone number
        $formats = $this->generatePhoneNumberFormats($phoneNumber);
        
        Log::info('🔍 findExistingLeadByPhoneNumber: Trying formats', [
            'formats' => $formats
        ]);

        // Try each format to find existing lead
        foreach ($formats as $index => $format) {
            Log::info('🔍 findExistingLeadByPhoneNumber: Trying format', [
                'index' => $index,
                'format' => $format
            ]);
            
            $lead = Lead::where('contact_no', $format)->first();
            if ($lead) {
                Log::info('✅ findExistingLeadByPhoneNumber: Lead found', [
                    'lead_id' => $lead->id,
                    'matched_format' => $format,
                    'existing_contact_no' => $lead->contact_no
                ]);
                return $lead;
            } else {
                Log::info('❌ findExistingLeadByPhoneNumber: No lead found for format', [
                    'format' => $format
                ]);
            }
        }

        Log::warning('❌ findExistingLeadByPhoneNumber: No lead found for any format', [
            'original_phone' => $phoneNumber,
            'tried_formats' => $formats
        ]);

        return null;
    }

    /**
     * Find existing operation lead by phone number with multiple format attempts
     */
    private function findExistingOperationLeadByPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return null;
        }

        Log::info('🔍 findExistingOperationLeadByPhoneNumber: Starting search', [
            'original_phone' => $phoneNumber
        ]);

        // Generate all possible formats for the phone number
        $formats = $this->generatePhoneNumberFormats($phoneNumber);
        
        Log::info('🔍 findExistingOperationLeadByPhoneNumber: Trying formats', [
            'formats' => $formats
        ]);

        // Try each format to find existing operation lead
        foreach ($formats as $index => $format) {
            Log::info('🔍 findExistingOperationLeadByPhoneNumber: Trying format', [
                'index' => $index,
                'format' => $format
            ]);
            
            $operationLead = OperationLead::where('contact_no', $format)->first();
            if ($operationLead) {
                Log::info('✅ findExistingOperationLeadByPhoneNumber: Operation lead found', [
                    'operation_lead_id' => $operationLead->id,
                    'matched_format' => $format,
                    'existing_contact_no' => $operationLead->contact_no
                ]);
                return $operationLead;
            } else {
                Log::info('❌ findExistingOperationLeadByPhoneNumber: No operation lead found for format', [
                    'format' => $format
                ]);
            }
        }

        Log::warning('❌ findExistingOperationLeadByPhoneNumber: No operation lead found for any format', [
            'original_phone' => $phoneNumber,
            'tried_formats' => $formats
        ]);

        return null;
    }

    /**
     * Generate all possible phone number formats for searching
     */
    private function generatePhoneNumberFormats($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return [];
        }

        // Clean the phone number
        $cleanNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        $cleanNumber = str_replace('+', '', $cleanNumber);
        
        $formats = [];
        
        // Add the original cleaned number
        $formats[] = $cleanNumber;
        
        // Add with 91 prefix
        if (!str_starts_with($cleanNumber, '91')) {
            $formats[] = '91' . $cleanNumber;
        }
        
        // Add without 91 prefix
        if (str_starts_with($cleanNumber, '91')) {
            $formats[] = substr($cleanNumber, 2);
        }
        
        // Handle numbers starting with 0 (like 07754966128)
        if (str_starts_with($cleanNumber, '0')) {
            $formats[] = substr($cleanNumber, 1); // Remove leading 0 (7754966128)
            $formats[] = '91' . substr($cleanNumber, 1); // Remove leading 0 and add 91 (91775496128)
        }
        
        // Handle 10-digit numbers (like 9565676128)
        if (strlen($cleanNumber) == 10 && !str_starts_with($cleanNumber, '91')) {
            $formats[] = '0' . $cleanNumber; // Add leading 0 (09565676128)
            $formats[] = '91' . $cleanNumber; // Add 91 prefix (919565676128)
        }
        
        // Handle 11-digit numbers with 91 (like 917417371337)
        if (strlen($cleanNumber) == 11 && str_starts_with($cleanNumber, '91')) {
            $formats[] = substr($cleanNumber, 2); // Remove 91 (7417371337)
            $formats[] = '0' . substr($cleanNumber, 2); // Remove 91 and add 0 (07417371337)
        }
        
        // Handle 12-digit numbers with 91 (like 917417371337)
        if (strlen($cleanNumber) == 12 && str_starts_with($cleanNumber, '91')) {
            $formats[] = substr($cleanNumber, 2); // Remove 91 (7417371337)
            $formats[] = '0' . substr($cleanNumber, 2); // Remove 91 and add 0 (07417371337)
        }
        
        // Add + prefix variations
        foreach ($formats as $format) {
            if (!str_starts_with($format, '+')) {
                $formats[] = '+' . $format;
            }
        }
        
        // Remove duplicates and return
        return array_unique($formats);
    }

    /**
     * Debug method to log all users with mobile numbers for troubleshooting
     */
    private function logAllUsersWithMobileNumbers()
    {
        try {
            $users = User::whereNotNull('mobile')
                        ->where('mobile', '!=', '')
                        ->select('id', 'f_name', 'l_name', 'mobile', 'email')
                        ->get();
            
            Log::info('🔍 DEBUG: All users with mobile numbers', [
                'total_users' => $users->count(),
                'users' => $users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->f_name . ' ' . $user->l_name,
                        'mobile' => $user->mobile,
                        'email' => $user->email
                    ];
                })->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch users for debugging', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper method to update lead recording
     */
    private function updateLeadRecording($lead, $data, $agentName)
    {
        try {
            // Get current recordings
            $recordings = [];
            if (!empty($lead->recording_url)) {
                try {
                    $recordings = is_array($lead->recording_url)
                        ? $lead->recording_url
                        : json_decode($lead->recording_url, true);
                } catch (\Exception $e) {
                    $recordings = [];
                }
            }
            
            // Append new recording if available
            if (!empty($data['recording_url'])) {
                $recordings[] = [
                    'url' => $data['recording_url'],
                    'metadata' => json_encode([
                        'datetime' => $data['start_stamp'],
                        'caller_agent' => $agentName,
                        'dialstatus' => $data['call_status'] ?? null,
                        'call_id' => $data['call_id'] ?? null,
                        'hangup_cause' => $data['hangup_cause'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'agent_name' => $agentName,
                        'call_direction' => $this->getCallDirectionLabel($data)
                    ])
                ];
            }
            
            $lead->update([
                'recording_url' => json_encode($recordings),
                'last_call_status' => $data['call_status'] ?? null
            ]);
            
            Log::info('✅ Lead recording updated (backup)', [
                'lead_id' => $lead->id,
                'recordings_count' => count($recordings)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update lead recording: ' . $e->getMessage());
        }
    }

    /**
     * Find existing job request by phone number with multiple format attempts
     */
    private function findExistingJobRequestByPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return null;
        }

        Log::info('🔍 findExistingJobRequestByPhoneNumber: Starting search', [
            'original_phone' => $phoneNumber
        ]);

        // Generate all possible formats for the phone number
        $formats = $this->generatePhoneNumberFormats($phoneNumber);
        
        Log::info('🔍 findExistingJobRequestByPhoneNumber: Trying formats', [
            'formats' => $formats
        ]);

        // Try each format to find existing job request
        foreach ($formats as $index => $format) {
            Log::info('🔍 findExistingJobRequestByPhoneNumber: Trying format', [
                'index' => $index,
                'format' => $format
            ]);
            
            $jobRequest = JobRequest::where('contact_no', $format)->first();
            if ($jobRequest) {
                Log::info('✅ findExistingJobRequestByPhoneNumber: JobRequest found', [
                    'job_request_id' => $jobRequest->id,
                    'matched_format' => $format,
                    'existing_contact_no' => $jobRequest->contact_no
                ]);
                return $jobRequest;
            } else {
                Log::info('❌ findExistingJobRequestByPhoneNumber: No job request found for format', [
                    'format' => $format
                ]);
            }
        }

        Log::warning('❌ findExistingJobRequestByPhoneNumber: No job request found for any format', [
            'original_phone' => $phoneNumber,
            'tried_formats' => $formats
        ]);

        return null;
    }

    /**
     * Save call details for JobRequest (one CallDetails row per Tata webhook / call).
     */
    private function saveCallDetailsForJobRequest($jobRequest, array $data, $agentName, $executiveUser, ?string $customerPhoneForRecord = null)
    {
        try {
            $callId = isset($data['call_id']) ? (string) $data['call_id'] : '';
            if ($callId !== '' && $this->callDetailsExistsForTataCallId($callId)) {
                Log::info('⚠️ CallDetails already exists for this call_id (JobRequest path), skipping duplicate', [
                    'call_id' => $callId,
                    'job_request_id' => $jobRequest->id,
                ]);

                return;
            }

            $callerNumber = $customerPhoneForRecord ?? ($data['caller_id_number'] ?? '');

            $callReceivedDatetime = isset($data['start_stamp'])
                ? Carbon::parse($data['start_stamp'])
                : now();

            $callDuration = null;
            if (isset($data['duration'])) {
                $callDuration = (int) $data['duration'];
            } elseif (isset($data['end_stamp']) && isset($data['start_stamp'])) {
                $startTime = Carbon::parse($data['start_stamp']);
                $endTime = Carbon::parse($data['end_stamp']);
                $callDuration = $endTime->diffInSeconds($startTime);
            }

            CallDetails::create([
                'lead_id' => null,
                'call_for' => 'job_request',
                'executive_id' => $executiveUser->id,
                'caller_id_number' => $callerNumber,
                'agent_number' => $this->getAgentNumber($data),
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
                'recording_url' => $data['recording_url'] ?? null,
                'call_received_datetime' => $callReceivedDatetime,
                'customer_name' => $jobRequest->customer_name,
                'lead_code' => $jobRequest->lead_id,
                'is_processed' => false,
                'call_duration' => $callDuration,
                'call_type' => $this->resolveCallTypeFromTataPayload($data),
                'call_source' => 'tata_job_request',
                'raw_data' => $data,
            ]);

            Log::info('Call details saved for JobRequest', [
                'job_request_id' => $jobRequest->id,
                'caller_number' => $callerNumber,
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save call details for JobRequest: ' . $e->getMessage());
        }
    }

    /**
     * Save call details for OperationLead (one CallDetails row per Tata webhook / call).
     */
    private function saveCallDetailsForOperationLead($operationLead, array $data, $agentName, $executiveUser, ?string $customerPhoneForRecord = null)
    {
        try {
            $callId = isset($data['call_id']) ? (string) $data['call_id'] : '';
            if ($callId !== '' && $this->callDetailsExistsForTataCallId($callId)) {
                Log::info('⚠️ CallDetails already exists for this call_id (OperationLead path), skipping duplicate', [
                    'call_id' => $callId,
                    'operation_lead_id' => $operationLead->id,
                ]);

                return;
            }

            $callerNumber = $customerPhoneForRecord ?? ($data['caller_id_number'] ?? '');

            $callReceivedDatetime = isset($data['start_stamp'])
                ? Carbon::parse($data['start_stamp'])
                : now();

            $callDuration = null;
            if (isset($data['duration'])) {
                $callDuration = (int) $data['duration'];
            } elseif (isset($data['end_stamp']) && isset($data['start_stamp'])) {
                $startTime = Carbon::parse($data['start_stamp']);
                $endTime = Carbon::parse($data['end_stamp']);
                $callDuration = $endTime->diffInSeconds($startTime);
            }

            CallDetails::create([
                'lead_id' => null,
                'call_for' => 'operation_lead',
                'executive_id' => $executiveUser->id,
                'caller_id_number' => $callerNumber,
                'agent_number' => $this->getAgentNumber($data),
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
                'recording_url' => $data['recording_url'] ?? null,
                'call_received_datetime' => $callReceivedDatetime,
                'customer_name' => $operationLead->customer_name,
                'lead_code' => $operationLead->lead_id,
                'is_processed' => false,
                'call_duration' => $callDuration,
                'call_type' => $this->resolveCallTypeFromTataPayload($data),
                'call_source' => 'tata_operation_lead',
                'raw_data' => $data,
            ]);

            Log::info('Call details saved for OperationLead', [
                'operation_lead_id' => $operationLead->id,
                'caller_number' => $callerNumber,
                'agent_name' => $agentName,
                'call_status' => $data['call_status'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save call details for OperationLead: ' . $e->getMessage());
        }
    }

    /**
     * Update related record (Lead/OperationLead/JobRequest) with final call status
     */
    private function updateRelatedRecordWithCallStatus($callRecord, array $data)
    {
        try {
            // For click-to-call/outbound, related CRM record (Lead/JobRequest/OperationLead)
            // should be searched using the *customer* number i.e. `call_to_number`.
            // Using `caller_id_number` (agent caller id) breaks lead lookup and prevents
            // recording_url update inside Lead.
            $callerNumber = $this->getCustomerPhoneNumberForLookup($data);
            $callStatus = $data['call_status'] ?? 'unknown';
            
            Log::info('🔄 WEBHOOK: Updating related record with final call status', [
                'call_details_id' => $callRecord->id,
                'caller_number' => $callerNumber,
                'call_status' => $callStatus
            ]);
            
            // Check in priority order: 1. JobRequest, 2. OperationLead, 3. Lead
            
            // FIRST: Check if JobRequest exists
            $jobRequest = $this->findExistingJobRequestByPhoneNumber($callerNumber);
            if ($jobRequest) {
                $this->updateJobRequestWithCallStatus($jobRequest, $data);
                Log::info('✅ WEBHOOK: JobRequest updated with final call status', [
                    'job_request_id' => $jobRequest->id,
                    'call_status' => $callStatus
                ]);
                return;
            }
            
            // SECOND: Check if OperationLead exists
            $operationLead = $this->findExistingOperationLeadByPhoneNumber($callerNumber);
            if ($operationLead) {
                $this->updateOperationLeadWithCallStatus($operationLead, $data);
                Log::info('✅ WEBHOOK: OperationLead updated with final call status', [
                    'operation_lead_id' => $operationLead->id,
                    'call_status' => $callStatus
                ]);
                return;
            }
            
            // THIRD: Check if normal Lead exists
            $lead = $this->findExistingLeadByPhoneNumber($callerNumber);
            if ($lead) {
                $this->updateLeadWithCallStatus($lead, $data);
                Log::info('✅ WEBHOOK: Lead updated with final call status', [
                    'lead_id' => $lead->id,
                    'call_status' => $callStatus
                ]);
                return;
            }
            
            Log::warning('⚠️ WEBHOOK: No related record found to update', [
                'caller_number' => $callerNumber,
                'call_details_id' => $callRecord->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ WEBHOOK: Failed to update related record with call status', [
                'error' => $e->getMessage(),
                'call_details_id' => $callRecord->id ?? 'unknown',
                'caller_number' => $data['caller_id_number'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Update JobRequest with final call status
     */
    private function updateJobRequestWithCallStatus($jobRequest, array $data)
    {
        try {
            // Get current recordings
            $recordings = [];
            if (!empty($jobRequest->recording_url)) {
                try {
                    $recordings = is_array($jobRequest->recording_url)
                        ? $jobRequest->recording_url
                        : json_decode($jobRequest->recording_url, true);
                } catch (\Exception $e) {
                    $recordings = [];
                }
            }
            
            // Append new recording if available
            if (!empty($data['recording_url'])) {
                $recordings[] = [
                    'url' => $data['recording_url'],
                    'metadata' => json_encode(array_merge([
                        'datetime' => $data['start_stamp'],
                        'caller_agent' => ($n = $this->getAgentName($data)),
                        'agent_name' => $n,
                        'dialstatus' => $data['call_status'] ?? null,
                        'call_id' => $data['call_id'] ?? null,
                        'hangup_cause' => $data['hangup_cause'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'source' => 'webhook_final_update',
                        'call_direction' => $this->getCallDirectionLabel($data),
                    ], !empty($data['call_to_number']) ? ['agent_number' => $data['call_to_number']] : []))
                ];
            }
            
            $jobRequest->update([
                'last_call_status' => $data['call_status'] ?? null,
                'recording_url' => json_encode($recordings)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update JobRequest with call status: ' . $e->getMessage());
        }
    }
    
    /**
     * Update OperationLead with final call status
     */
    private function updateOperationLeadWithCallStatus($operationLead, array $data)
    {
        try {
            // Get current recordings
            $recordings = [];
            if (!empty($operationLead->recording_url)) {
                try {
                    $recordings = is_array($operationLead->recording_url)
                        ? $operationLead->recording_url
                        : json_decode($operationLead->recording_url, true);
                } catch (\Exception $e) {
                    $recordings = [];
                }
            }
            
            // Append new recording if available
            if (!empty($data['recording_url'])) {
                $recordings[] = [
                    'url' => $data['recording_url'],
                    'metadata' => json_encode(array_merge([
                        'datetime' => $data['start_stamp'],
                        'caller_agent' => ($n = $this->getAgentName($data)),
                        'agent_name' => $n,
                        'dialstatus' => $data['call_status'] ?? null,
                        'call_id' => $data['call_id'] ?? null,
                        'hangup_cause' => $data['hangup_cause'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'source' => 'webhook_final_update',
                        'call_direction' => $this->getCallDirectionLabel($data),
                    ], !empty($data['call_to_number']) ? ['agent_number' => $data['call_to_number']] : []))
                ];
            }
            
            $operationLead->update([
                'last_call_status' => $data['call_status'] ?? null,
                'recording_url' => json_encode($recordings)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update OperationLead with call status: ' . $e->getMessage());
        }
    }
    
    /**
     * Update Lead with final call status
     */
    private function updateLeadWithCallStatus($lead, array $data)
    {
        try {
            // Get current recordings
            $recordings = [];
            if (!empty($lead->recording_url)) {
                try {
                    $recordings = is_array($lead->recording_url)
                        ? $lead->recording_url
                        : json_decode($lead->recording_url, true);
                } catch (\Exception $e) {
                    $recordings = [];
                }
            }
            
            // Append new recording if available
            if (!empty($data['recording_url'])) {
                $recordings[] = [
                    'url' => $data['recording_url'],
                    'metadata' => json_encode(array_merge([
                        'datetime' => $data['start_stamp'],
                        'caller_agent' => ($n = $this->getAgentName($data)),
                        'agent_name' => $n,
                        'dialstatus' => $data['call_status'] ?? null,
                        'call_id' => $data['call_id'] ?? null,
                        'hangup_cause' => $data['hangup_cause'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'source' => 'webhook_final_update',
                        'call_direction' => $this->getCallDirectionLabel($data),
                    ], !empty($data['call_to_number']) ? ['agent_number' => $data['call_to_number']] : []))
                ];
            }
            
            $lead->update([
                'last_call_status' => $data['call_status'] ?? null,
                'recording_url' => json_encode($recordings)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update Lead with call status: ' . $e->getMessage());
        }
    }
    
    /**
     * Update OperationLead active deployment recording
     */
    private function updateOperationLeadActiveDeploymentRecording($operationLead, array $data, $agentName, $executiveUser, $jobRequest)
    {
        try {
            // Get current active deployment recordings
            $activeDepRecordings = [];
            if (!empty($operationLead->active_deployment_recording_url)) {
                try {
                    $activeDepRecordings = is_array($operationLead->active_deployment_recording_url)
                        ? $operationLead->active_deployment_recording_url
                        : json_decode($operationLead->active_deployment_recording_url, true);
                } catch (\Exception $e) {
                    $activeDepRecordings = [];
                }
            }
            
            // Append new recording
            if (!empty($data['recording_url'])) {
                $activeDepRecordings[] = [
                    'url' => $data['recording_url'],
                    'metadata' => json_encode([
                        'datetime' => $data['start_stamp'],
                        'caller_agent' => $agentName,
                        'dialstatus' => $data['call_status'] ?? null,
                        'call_id' => $data['call_id'] ?? null,
                        'hangup_cause' => $data['hangup_cause'] ?? null,
                        'duration' => $data['duration'] ?? null,
                        'agent_name' => $agentName,
                        'emp_phone' => $executiveUser->mobile ?? null,
                        'freelancer_name' => $jobRequest->name ?? 'Unknown',
                        'freelancer_id' => $jobRequest->id,
                        'source' => 'webhook_final_update'
                        ,'call_direction' => $this->getCallDirectionLabel($data)
                    ])
                ];
            }
            
            $operationLead->update([
                'active_deployment_recording_url' => json_encode($activeDepRecordings)
            ]);
            
            Log::info('✅ OperationLead active deployment recording updated', [
                'operation_lead_id' => $operationLead->id,
                'job_request_id' => $jobRequest->id,
                'recordings_count' => count($activeDepRecordings)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update OperationLead active deployment recording: ' . $e->getMessage());
        }
    }

    /**
     * Find and update existing call record from dialplan
     */
    private function findAndUpdateExistingCallRecord(array $data)
    {
        try {
            // Inbound-only: dialplan creates these rows. Outbound/click-to-call must always
            // create a separate CallDetails row via saveCallLog (never merge into an inbound row).
            $dir = strtolower(trim((string) ($data['direction'] ?? '')));
            if (in_array($dir, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)) {
                Log::info('WEBHOOK: Skipping dialplan merge for outbound/click-to-call (separate call log row)', [
                    'call_id' => $data['call_id'] ?? null,
                    'direction' => $dir,
                ]);

                return null;
            }

            $callerNumber = $data['caller_id_number'];
            $callId = isset($data['call_id']) ? trim((string) $data['call_id']) : '';

            // Normalize phone number to match dialplan format
            $normalizedCallerNumber = $this->normalizePhoneNumber($callerNumber);

            Log::info('🔍 WEBHOOK: Searching for existing call record', [
                'original_caller_number' => $callerNumber,
                'normalized_caller_number' => $normalizedCallerNumber,
                'call_id' => $callId !== '' ? $callId : null,
                'webhook_status' => $data['call_status'] ?? 'unknown',
            ]);

            $existingCall = null;

            // 1) Primary: Tata call_id on dialplan row (correct leg even if caller format differs / duplicate webhooks)
            if ($callId !== '') {
                $existingCall = CallDetails::findTataDialplanByTataCallId($callId);
                if ($existingCall) {
                    Log::info('✅ WEBHOOK: Found existing call record by Tata call_id', [
                        'call_details_id' => $existingCall->id,
                        'call_id' => $callId,
                        'caller_number' => $callerNumber,
                        'matched_caller_number' => $existingCall->caller_id_number,
                        'strategy' => 'tata_call_id',
                    ]);
                }
            }

            // 2) Fallback: ringing dialplan row for this caller (no / missing call_id in payload)
            if (! $existingCall) {
                $existingCall = CallDetails::where(function ($query) use ($callerNumber, $normalizedCallerNumber) {
                    $query->where('caller_id_number', $callerNumber)
                        ->orWhere('caller_id_number', $normalizedCallerNumber);
                })
                    ->where('call_status', 'ringing')
                    ->where('call_source', 'tata_dialplan')
                    ->latest('call_received_datetime')
                    ->first();
                if ($existingCall) {
                    Log::info('✅ WEBHOOK: Found existing call record by ringing + caller', [
                        'call_details_id' => $existingCall->id,
                        'caller_number' => $callerNumber,
                        'normalized_caller_number' => $normalizedCallerNumber,
                        'matched_caller_number' => $existingCall->caller_id_number,
                        'strategy' => 'ringing_caller',
                    ]);
                }
            }

            // 3) Last resort: only when webhook has no call_id — avoid wrong leg when same customer calls twice
            if (! $existingCall && $callId === '') {
                $fiveMinutesAgo = now()->subMinutes(5);
                $existingCall = CallDetails::where(function ($query) use ($callerNumber, $normalizedCallerNumber) {
                    $query->where('caller_id_number', $callerNumber)
                        ->orWhere('caller_id_number', $normalizedCallerNumber);
                })
                    ->where('call_source', 'tata_dialplan')
                    ->where('call_received_datetime', '>=', $fiveMinutesAgo)
                    ->latest('call_received_datetime')
                    ->first();
                if ($existingCall) {
                    Log::info('✅ WEBHOOK: Found existing call record by time range (no call_id in webhook)', [
                        'call_details_id' => $existingCall->id,
                        'caller_number' => $callerNumber,
                        'normalized_caller_number' => $normalizedCallerNumber,
                        'matched_caller_number' => $existingCall->caller_id_number,
                        'time_range' => 'last_5_minutes',
                    ]);
                }
            }
            
            
            if ($existingCall) {
                Log::info('✅ WEBHOOK: Found existing call record from dialplan', [
                    'call_details_id' => $existingCall->id,
                    'caller_number' => $callerNumber,
                    'current_status' => $existingCall->call_status,
                    'call_source' => $existingCall->call_source,
                    'call_received_datetime' => $existingCall->call_received_datetime
                ]);
                
                // Calculate call duration if available
                $callDuration = null;
                if (isset($data['duration'])) {
                    $callDuration = (int) $data['duration'];
                } elseif (isset($data['end_stamp']) && isset($data['start_stamp'])) {
                    $startTime = Carbon::parse($data['start_stamp']);
                    $endTime = Carbon::parse($data['end_stamp']);
                    $callDuration = $endTime->diffInSeconds($startTime);
                }

                $dir = strtolower((string) ($data['direction'] ?? ''));
                $resolvedCallType = 'inbound';
                if (in_array($dir, ['outbound', 'clicktocall', 'click_to_call', 'click-to-call'], true)) {
                    $resolvedCallType = 'outbound';
                } elseif (! empty($data['call_type'])) {
                    $resolvedCallType = strtolower((string) $data['call_type']);
                }
                
                // Update the existing call record with final call data
                $existingCall->update([
                    'call_status' => $data['call_status'] ?? 'unknown',
                    'recording_url' => $data['recording_url'] ?? null,
                    'call_duration' => $callDuration,
                    'call_notes' => 'Call completed - updated from webhook',
                    'call_type' => $resolvedCallType,
                    'raw_data' => $data,
                    'is_processed' => true // Mark as processed
                ]);
                
                Log::info('✅ WEBHOOK: Updated existing call record', [
                    'call_details_id' => $existingCall->id,
                    'final_status' => $data['call_status'] ?? 'unknown',
                    'duration' => $callDuration,
                    'has_recording' => !empty($data['recording_url']),
                    'old_status' => 'ringing',
                    'new_status' => $data['call_status'] ?? 'unknown'
                ]);
                
                return $existingCall;
            } else {
                Log::info('⚠️ WEBHOOK: No existing call record found from dialplan', [
                    'caller_number' => $callerNumber,
                    'call_id' => $callId !== '' ? $callId : null,
                    'had_call_id' => $callId !== '',
                ]);
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('❌ WEBHOOK: Failed to find/update existing call record', [
                'error' => $e->getMessage(),
                'caller_number' => $data['caller_id_number'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function processMissedCallbackOutboundWebhook(array $data): void
    {
        $ref = MissedCallbackSequenceService::extractRefFromTataPayload($data);
        if (! $ref) {
            return;
        }
        [$sid, $step] = $ref;
        $seq = MissedCallbackSequence::find($sid);
        if (! $seq) {
            return;
        }
        app(MissedCallbackSequenceService::class)->handleOutboundDisposition($seq, $step, $data);
    }

    private function maybeStartMissedCallbackSequenceForLead(Lead $lead, array $data, User $exec): void
    {
        app(MissedCallbackSequenceService::class)->startOrIgnore(
            $lead,
            MissedCallbackSequence::LEAD_TYPE_LEAD,
            $data,
            $exec,
            ! empty($data['call_id']) ? (string) $data['call_id'] : null
        );
    }
}
