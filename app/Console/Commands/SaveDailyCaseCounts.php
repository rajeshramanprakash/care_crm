<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cases;
use App\Models\DailyCaseCount;
use Carbon\Carbon;

class SaveDailyCaseCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:save-daily-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save daily case counts for each department';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $departments = [
            'Sales' => ['name' => 'Sales', 'role_id' => 2],
            'Doctor' => ['name' => 'Doctor', 'role_id' => 3],
            'Bill' => ['name' => 'Bill', 'role_id' => 5],
            'Lab' => ['name' => 'Lab', 'role_id' => 6],
            'Dispatcher' => ['name' => 'Dispatcher', 'role_id' => 7],
            'MedicineVital' => ['name' => 'MedicineVital', 'role_id' => 4],
            'SubAdmin' => ['name' => 'SubAdmin', 'role_id' => 11],
        ];
        $today = Carbon::today();
        foreach ($departments as $key => $deptData) {
            $main_claim_cases = Cases::where('is_post_1', 0)->where('is_post_2', 0)->where('forward_status', 1)
                ->where(function ($query) use ($deptData) {
                    $query->where('assign_member_post_role', $deptData['role_id'])
                        ->orWhere('assign_member_role', $deptData['role_id']);
                })->groupBy('id')->get()->count();
            $post_claim_cases = Cases::where('is_post_1', 1)->where('is_post_2', 0)->where('forward_status', 1)
                ->where(function ($query) use ($deptData) {
                    $query->where('assign_member_post_role', $deptData['role_id'])
                        ->orWhere('assign_member_role', $deptData['role_id']);
                })->groupBy('id')->get()->count();
            $post_two_claim_cases = Cases::where('is_post_2', 1)->where('forward_status', 1)
                ->where(function ($query) use ($deptData) {
                    $query->where('assign_member_post_role', $deptData['role_id'])
                        ->orWhere('assign_member_role', $deptData['role_id']);
                })->groupBy('id')->get()->count();
            DailyCaseCount::updateOrCreate(
                [
                    'department' => $deptData['name'],
                    'role_id' => $deptData['role_id'],
                    'date' => $today,
                ],
                [
                    'main_claim_cases' => $main_claim_cases,
                    'post_claim_cases' => $post_claim_cases,
                    'post_two_claim_cases' => $post_two_claim_cases,
                ]
            );
        }
        $this->info('Daily case counts saved successfully.');
    }
}
