<?php

namespace App\Imports;

use App\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocationBulkImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private int $createdCount = 0;

    private int $updatedCount = 0;

    private int $errorCount = 0;

    private int $skippedDuplicateCount = 0;

    /** @var array<string, int> */
    private array $seenInFile = [];

    /** @var array<int, string> */
    private array $importErrors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $name = $this->cell($row, [
                'city_town_name',
                'citytownname',
                'city_town',
                'location_name',
                'location',
                'name',
            ]);
            $state = $this->cell($row, ['state']);
            $tier = $this->cell($row, ['tier']);

            if ($name === '') {
                continue;
            }

            if ($state === '' || $tier === '') {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: City / Town Name, State, and Tier are all required.";
                continue;
            }

            $nameKey = mb_strtolower(trim($name));

            if (isset($this->seenInFile[$nameKey])) {
                $this->skippedDuplicateCount++;
                $firstRow = $this->seenInFile[$nameKey];
                $this->importErrors[] = "Row {$rowNumber}: Duplicate city \"{$name}\" skipped (same as row {$firstRow}).";
                continue;
            }

            $this->seenInFile[$nameKey] = $rowNumber;

            try {
                $location = Location::whereRaw('LOWER(TRIM(name)) = ?', [$nameKey])->first();

                if ($location) {
                    $location->update([
                        'state' => $state,
                        'tier' => $tier,
                    ]);
                    $this->updatedCount++;
                } else {
                    Location::create([
                        'name' => $name,
                        'state' => $state,
                        'tier' => $tier,
                    ]);
                    $this->createdCount++;
                }
            } catch (\Throwable $e) {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }
    }

    private function cell(Collection $row, array $keys): string
    {
        foreach ($keys as $key) {
            if ($row->has($key) && $row->get($key) !== null && trim((string) $row->get($key)) !== '') {
                return trim((string) $row->get($key));
            }
        }

        return '';
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function getSkippedDuplicateCount(): int
    {
        return $this->skippedDuplicateCount;
    }
}
