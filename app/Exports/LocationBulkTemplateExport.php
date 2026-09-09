<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationBulkTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'City / Town Name',
            'State',
            'Tier',
        ];
    }

    public function array(): array
    {
        return [
            ['Mumbai', 'Maharashtra', 'Tier 1'],
            ['Jaipur', 'Rajasthan', 'Tier 2'],
            ['Shimla', 'Himachal Pradesh', 'Tier 3'],
        ];
    }

    public function title(): string
    {
        return 'Locations';
    }
}
