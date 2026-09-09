<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class B2BIndividualLeadBulkTemplateExport implements FromArray, WithHeadings, WithTitle
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
            ['9876543210', 'Nursing Care', 'Need caregiver for elderly parent', '2'],
            ['9123456789', '', '', '5'],
        ];
    }

    public function title(): string
    {
        return 'B2B Individual Leads';
    }
}
