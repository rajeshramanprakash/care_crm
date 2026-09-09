<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['step_index' => 1, 'kind' => 'immediate', 'delay_minutes' => null, 'window_start' => null, 'window_end' => null, 'day_offset' => 0],
            ['step_index' => 2, 'kind' => 'delay_minutes', 'delay_minutes' => 5, 'window_start' => null, 'window_end' => null, 'day_offset' => 0],
            ['step_index' => 3, 'kind' => 'delay_minutes', 'delay_minutes' => 15, 'window_start' => null, 'window_end' => null, 'day_offset' => 0],
            ['step_index' => 4, 'kind' => 'delay_minutes', 'delay_minutes' => 45, 'window_start' => null, 'window_end' => null, 'day_offset' => 0],
            ['step_index' => 5, 'kind' => 'delay_minutes', 'delay_minutes' => 45, 'window_start' => null, 'window_end' => null, 'day_offset' => 0],
            ['step_index' => 6, 'kind' => 'calendar_window', 'delay_minutes' => null, 'window_start' => '18:00:00', 'window_end' => '21:00:00', 'day_offset' => 0],
            ['step_index' => 7, 'kind' => 'calendar_window', 'delay_minutes' => null, 'window_start' => '10:00:00', 'window_end' => '11:00:00', 'day_offset' => 1],
            ['step_index' => 8, 'kind' => 'calendar_window', 'delay_minutes' => null, 'window_start' => '17:00:00', 'window_end' => '21:00:00', 'day_offset' => 1],
            ['step_index' => 9, 'kind' => 'calendar_window', 'delay_minutes' => null, 'window_start' => '10:00:00', 'window_end' => '11:00:00', 'day_offset' => 2],
            ['step_index' => 10, 'kind' => 'calendar_window', 'delay_minutes' => null, 'window_start' => '14:00:00', 'window_end' => '21:00:00', 'day_offset' => 2],
        ];
        foreach ($rows as $r) {
            DB::table('missed_callback_step_configs')->insert(array_merge($r, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('missed_callback_step_configs')->truncate();
    }
};
