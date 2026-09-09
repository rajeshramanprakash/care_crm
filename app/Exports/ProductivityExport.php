<?php

namespace App\Exports;

use App\Models\Productivity;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProductivityExport implements FromView
{
    protected $userId;
    protected $month;

    public function __construct($userId, $month)
    {
        $this->userId = $userId;
        $this->month = $month;
    }

    public function view(): View
    {
        $productivity = Productivity::selectRaw("
                DATE_FORMAT(date, '%Y-%m-%d') as day,
                SUM(CASE WHEN role_id = 3 THEN file_count ELSE 0 END) as doctor,
                SUM(CASE WHEN role_id = 5 THEN file_count ELSE 0 END) as billing,
                SUM(CASE WHEN role_id = 4 THEN file_count ELSE 0 END) as medicine,
                SUM(CASE WHEN role_id = 6 THEN file_count ELSE 0 END) as lab,
                SUM(CASE WHEN role_id = 7 THEN file_count ELSE 0 END) as dispatch,
                SUM(file_count) as total_files,
                CASE
                    WHEN SUM(file_count) < 5 THEN 'Absent'
                    WHEN SUM(file_count) BETWEEN 5 AND 11 THEN 'Halfday'
                    ELSE 'Present'
                END as status
            ")
            ->where('user_id', $this->userId)
            ->whereMonth('date', date('m', strtotime($this->month)))
            ->whereYear('date', date('Y', strtotime($this->month)))
            ->groupBy('date')
            ->get();

        return view('exports.productivity', compact('productivity'));
    }
}
