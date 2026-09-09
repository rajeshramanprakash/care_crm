<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Cases;
use App\Models\Query;
use App\Models\User;
use App\Services\ExpoNotificationService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TicketService
{
    /**
     * Create a new ticket when query is created
     */
    public function createTicketFromQuery(Query $query, Cases $case, User $vendor, User $tpa, $caseType = 'normal')
    {
        // Generate unique ticket number
        $ticketNumber = 'TKT-' . date('Y') . '-' . str_pad(Ticket::count() + 1, 6, '0', STR_PAD_LEFT);

        // Create ticket
        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'case_id' => $case->id,
            'query_id' => $query->id,
            'vendor_id' => $vendor->id,
            'tpa_id' => $tpa->id,
            'created_by' => auth()->id(),
            'case_type' => $caseType,
            'status' => 'pending',
            'is_active' => true,
        ]);

        // Create initial messages
        $this->createInitialMessages($ticket, $case);

        // Send notifications to vendor and TPA
        $this->sendTicketCreationNotifications($ticket, $case);

        return $ticket;
    }

    /**
     * Create initial messages for vendor and TPA
     */
    private function createInitialMessages(Ticket $ticket, Cases $case)
    {
        // Message for Vendor
        $vendorMessage = "Query Ready\n";
        $vendorMessage .= "Case Code: {$case->case_code}\n";
        $vendorMessage .= "Claim No: {$case->claim_no}\n";
        $vendorMessage .= "Patient Name: {$case->patient_name}\n";
        $vendorMessage .= "Your query is ready. We will inform you when to submit.";

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => auth()->id(),
            'recipient_id' => $ticket->vendor_id,
            'message_type' => TicketMessage::TYPE_VENDOR_NOTIFICATION,
            'message' => $vendorMessage,
        ]);

        // Message for TPA
        $tpaMessage = "Query Ready for Review\n";
        $tpaMessage .= "Claim No: {$case->claim_no}\n";
        $tpaMessage .= "Patient Name: {$case->patient_name}\n";
        $tpaMessage .= "Kindly let us know when this can be submitted by vendor.";

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => auth()->id(),
            'recipient_id' => $ticket->tpa_id,
            'message_type' => TicketMessage::TYPE_TPA_NOTIFICATION,
            'message' => $tpaMessage,
        ]);
    }

    /**
     * TPA approves submission with time
     */
    public function approveSubmission(Ticket $ticket, $submissionTime)
    {
        $ticket->update([
            'submission_time' => $submissionTime,
            'status' => 'approved',
        ]);

        // Message to vendor about approval
        $approvalMessage = "TPA has approved submission at {$submissionTime}\n";
        $approvalMessage .= "Please submit the claim.";

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $ticket->tpa_id,
            'recipient_id' => $ticket->vendor_id,
            'message_type' => TicketMessage::TYPE_TPA_APPROVAL,
            'message' => $approvalMessage,
        ]);

        // Send notification to vendor about approval
        $this->sendTicketApprovalNotifications($ticket, $submissionTime);

        return $ticket;
    }

    /**
     * Vendor submits the claim
     */
    public function submitClaim(Ticket $ticket)
    {
        $ticket->update([
            'status' => 'submitted',
        ]);

        // Message to TPA about submission
        $submissionMessage = "Claim submitted by vendor\n";
        $submissionMessage .= "Claim No: {$ticket->case->claim_no}\n";
        $submissionMessage .= "Patient Name: {$ticket->case->name}\n";
        $submissionMessage .= "Submission completed. Please process further.";

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $ticket->vendor_id,
            'recipient_id' => $ticket->tpa_id,
            'message_type' => TicketMessage::TYPE_VENDOR_SUBMISSION,
            'message' => $submissionMessage,
        ]);

        // Send notification to TPA about submission
        $this->sendTicketSubmissionNotifications($ticket);

        return $ticket;
    }

    /**
     * Close ticket
     */
    public function closeTicket(Ticket $ticket)
    {
        $ticket->update([
            'is_active' => false,
            'status' => 'closed',
        ]);

        // System message
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => null,
            'recipient_id' => null,
            'message_type' => TicketMessage::TYPE_SYSTEM,
            'message' => 'Ticket has been closed.',
        ]);

        // Send notification to relevant users about ticket closure
        $this->sendTicketCloseNotifications($ticket);

        return $ticket;
    }

    /**
     * Get tickets for a specific user
     */
    public function getUserTickets(User $user, $status = null)
    {
        $query = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
            ->where(function ($q) use ($user) {
                $q->where('vendor_id', $user->id)
                  ->orWhere('tpa_id', $user->id)
                  ->orWhere('created_by', $user->id);
            });

        // Filter by status
        if ($status && $status !== 'all') {
            if ($status === 'closed') {
                $query->where('status', 'closed');
            } else {
                $query->where('status', $status);
            }
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        // Filter messages based on user role for each ticket
        foreach ($tickets as $ticket) {
            $ticket->messages = $this->getRoleSpecificMessages($ticket->messages, $user);
        }

        return $tickets;
    }

    /**
     * Get tickets for a specific user with pagination
     */
    public function getUserTicketsPaginated(User $user, $status = null, $page = 1, $perPage = 10)
    {
        $query = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages.sender', 'messages.recipient'])
            ->where(function ($q) use ($user) {
                $q->where('vendor_id', $user->id)
                  ->orWhere('tpa_id', $user->id)
                  ->orWhere('created_by', $user->id);
            });

        // Filter by status
        if ($status && $status !== 'all') {
            if ($status === 'closed') {
                $query->where('status', 'closed');
            } else {
                $query->where('status', $status);
            }
        }

        // Sort: active tickets first (by newest), then closed tickets (by newest)
        $tickets = $query->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage, ['*'], 'page', $page);

        // Filter messages based on user role for each ticket
        foreach ($tickets->items() as $ticket) {
            $ticket->messages = $this->getRoleSpecificMessages($ticket->messages, $user);
        }

        return $tickets;
    }

    /**
     * Get role-specific messages for a user
     */
        public function getRoleSpecificMessages($messages, User $user)
    {
        // Check if user is Vendor (role_id = 10)
        if ($user->role_id == 10) {
            // Vendor should only see vendor notifications and TPA approvals
            return $messages->filter(function ($message) {
                return in_array($message->message_type, [
                    TicketMessage::TYPE_VENDOR_NOTIFICATION,
                    TicketMessage::TYPE_TPA_APPROVAL,
                    TicketMessage::TYPE_SYSTEM
                ]);
            });
        }
        // Check if user is TPA (role_id = 8)
        if ($user->role_id == 8) {
            // TPA should only see TPA notifications and vendor submissions
            return $messages->filter(function ($message) {
                return in_array($message->message_type, [
                    TicketMessage::TYPE_TPA_NOTIFICATION,
                    TicketMessage::TYPE_VENDOR_SUBMISSION,
                    TicketMessage::TYPE_SYSTEM
                ]);
            });
        }

        // For other roles (admin, etc.), show all messages
        return $messages;
    }

    /**
     * Get all tickets for admin
     */
    public function getAllTickets($status = null)
    {
        $query = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages']);

        // Filter by status
        if ($status && $status !== 'all') {
            if ($status === 'closed') {
                $query->where('status', 'closed');
            } else {
                $query->where('status', $status);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all tickets for admin with pagination
     */
    public function getAllTicketsPaginated($status = null, $page = 1, $perPage = 10)
    {
        $query = Ticket::with(['case', 'queryData', 'vendor', 'tpa', 'messages']);

        // Filter by status
        if ($status && $status !== 'all') {
            if ($status === 'closed') {
                $query->where('status', 'closed');
            } else {
                $query->where('status', $status);
            }
        }

        // Sort: active tickets first (by newest), then closed tickets (by newest)
        return $query->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Mark message as read
     */
    public function markMessageAsRead(TicketMessage $message)
    {
        $message->update(['is_read' => true]);
        return $message;
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(User $user)
    {
        $query = TicketMessage::where('recipient_id', $user->id)
            ->where('is_read', false);

        // Check if user is Vendor (role_id = 10)
        if ($user->role_id == 10) {
            // Vendor should only see vendor notifications and TPA approvals
            $query->whereIn('message_type', [
                TicketMessage::TYPE_VENDOR_NOTIFICATION,
                TicketMessage::TYPE_TPA_APPROVAL
            ]);
        }
        // Check if user is TPA (role_id = 8)
        elseif ($user->role_id == 8) {
            // TPA should only see TPA notifications and vendor submissions
            $query->whereIn('message_type', [
                TicketMessage::TYPE_TPA_NOTIFICATION,
                TicketMessage::TYPE_VENDOR_SUBMISSION
            ]);
        }
        // For other roles (admin, etc.), show all messages
        // No additional filtering needed

        return $query->count();
    }

    /**
     * Get role-specific unread count for sidebar badges
     */
    public function getRoleSpecificUnreadCount(User $user)
    {
        return $this->getUnreadCount($user);
    }

    /**
     * Send notifications when ticket is created
     */
    private function sendTicketCreationNotifications(Ticket $ticket, Cases $case)
    {
        try {
            // Notify vendor about new ticket
            ExpoNotificationService::send(
                [$ticket->vendor_id],
                'New Ticket Created',
                "New ticket {$ticket->ticket_number} created for case {$case->case_code}. Please wait for TPA approval."
            );

            // Notify TPA about new ticket requiring review
            ExpoNotificationService::send(
                [$ticket->tpa_id],
                'Ticket Requires Review',
                "Ticket {$ticket->ticket_number} for case {$case->case_code} needs your review and approval."
            );

            \Log::info("Ticket creation notifications sent for ticket: {$ticket->id}");
        } catch (\Exception $e) {
            \Log::error("Error sending ticket creation notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notifications when TPA approves submission
     */
    private function sendTicketApprovalNotifications(Ticket $ticket, $submissionTime)
    {
        try {
            // Notify vendor that TPA has approved submission
            ExpoNotificationService::send(
                [$ticket->vendor_id],
                'Submission Approved',
                "TPA has approved your submission for ticket {$ticket->ticket_number}. Submit by {$submissionTime}."
            );

            \Log::info("Ticket approval notifications sent for ticket: {$ticket->id}");
        } catch (\Exception $e) {
            \Log::error("Error sending ticket approval notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notifications when vendor submits claim
     */
    private function sendTicketSubmissionNotifications(Ticket $ticket)
    {
        try {
            // Notify TPA that vendor has submitted claim
            ExpoNotificationService::send(
                [$ticket->tpa_id],
                'Claim Submitted',
                "Vendor has submitted claim for ticket {$ticket->ticket_number}. Please process further."
            );

            \Log::info("Ticket submission notifications sent for ticket: {$ticket->id}");
        } catch (\Exception $e) {
            \Log::error("Error sending ticket submission notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notifications when ticket is closed
     */
    private function sendTicketCloseNotifications(Ticket $ticket)
    {
        try {
            // Determine users to notify based on ticket status
            $usersToNotify = [];
            
            // Always notify vendor and TPA
            $usersToNotify[] = $ticket->vendor_id;
            $usersToNotify[] = $ticket->tpa_id;

            if (!empty($usersToNotify)) {
                ExpoNotificationService::send(
                    $usersToNotify,
                    'Ticket Closed',
                    "Ticket {$ticket->ticket_number} has been closed."
                );
            }

            \Log::info("Ticket close notifications sent for ticket: {$ticket->id}");
        } catch (\Exception $e) {
            \Log::error("Error sending ticket close notifications: " . $e->getMessage());
        }
    }
}
