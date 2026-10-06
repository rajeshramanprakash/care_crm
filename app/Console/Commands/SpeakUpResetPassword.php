<?php

namespace App\Console\Commands;

use App\Models\SpeakUpAccessLog;
use App\Models\SpeakUpSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SpeakUpResetPassword extends Command
{
    protected $signature = 'speakup:reset-password {--show-url : Only print the current secret review URL}';

    protected $description = 'Reset the Speak Up review password (when the Admin forgot it) or print the secret URL';

    public function handle(): int
    {
        $setting = SpeakUpSetting::current();

        if ($this->option('show-url')) {
            $this->line(route('speak_up.review.gate', $setting->secret_slug));

            return self::SUCCESS;
        }

        $password = (string) $this->secret('New Speak Up password (min 8 chars, letters + numbers)');
        if (strlen($password) < 8 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password)) {
            $this->error('Password must be at least 8 characters with letters and numbers.');

            return self::FAILURE;
        }
        if ($password !== (string) $this->secret('Confirm password')) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $setting->update([
            'password_hash' => Hash::make($password),
            'password_changed_at' => now(),
            'password_changed_by' => null,
        ]);
        SpeakUpAccessLog::create(['event' => 'password_reset_cli', 'success' => true, 'details' => 'Reset from server console']);

        $this->info('Speak Up password reset.');

        return self::SUCCESS;
    }
}
