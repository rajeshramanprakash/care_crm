<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class B2BCorporateLeadBulkTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'number',
            'service',
            'detail',
            'bulk',
        ];
    }

    public function array(): array
    {
        return [
            ['9876543210', 'Nursing Care', 'Need 2 nurses for home care in Delhi', '5'],
            ['', 'Doctor consultation', 'Corporate wellness camp — 20 employees', '20'],
        ];
    }

    public function title(): string
    {
        return 'B2B Corporate Leads';
    }
}
