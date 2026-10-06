<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Support\DeskClient;
use Illuminate\Console\Command;

class DeskSync extends Command
{
    protected $signature = 'desk:sync';

    protected $description = 'Send unsent customer bug tickets to Ashniva Desk and pull status / replies for open ones';

    public function handle(): int
    {
        if (! DeskClient::configured()) {
            $this->warn('DESK_API_KEY is not set — nothing to do.');

            return self::SUCCESS;
        }

        $base = SupportTicket::query()->where('type', 'bug')->whereNotIn('status', ['closed']);

        $sent = 0;
        foreach ((clone $base)->whereNull('desk_ticket_id')->where('created_at', '>=', now()->subDays(14))->limit(25)->get() as $ticket) {
            $sent += DeskClient::push($ticket) ? 1 : 0;
        }

        $refreshed = 0;
        foreach ((clone $base)->whereNotNull('desk_ticket_id')->orderBy('desk_synced_at')->limit(50)->get() as $ticket) {
            $refreshed += DeskClient::refresh($ticket) ? 1 : 0;
        }

        $this->info("Sent: {$sent}, refreshed: {$refreshed}");

        return self::SUCCESS;
    }
}
