<?php

namespace App\Imports;

use App\Models\B2BUser;
use App\Services\B2BIndividualLeadService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class B2BIndividualLeadBulkImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private int $insertedCount = 0;

    private int $errorCount = 0;

    /** @var array<int, string> */
    private array $importErrors = [];

    public function __construct(
        protected B2BUser $b2bUser,
        protected B2BIndividualLeadService $leadService
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $number = $this->cell($row, ['number', 'mobile', 'contact_no', 'phone']);
            $service = $this->cell($row, ['service', 'service_requirement']);
            $detail = $this->cell($row, ['detail', 'details', 'detail_box', 'query_remark', 'query_remarks', 'remark']);
            $bulkRaw = $this->cell($row, ['bulk', 'bulk_qty', 'quantity', 'qty']);

            if ($number === '' && $service === '' && $detail === '' && $bulkRaw === '') {
                continue;
            }

            $digits = preg_replace('/\D+/', '', $number) ?? '';
            if (strlen($digits) !== 10) {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: number is required (10 digits).";

                continue;
            }

            if ($bulkRaw === '' || (int) $bulkRaw < 1) {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: bulk is required (min 1).";

                continue;
            }

            try {
                $this->leadService->createLead($this->b2bUser, [
                    'number' => $digits,
                    'service' => $service !== '' ? $service : null,
                    'detail' => $detail !== '' ? $detail : null,
                    'bulk' => (int) $bulkRaw,
                ], 'bulk');
                $this->insertedCount++;
            } catch (\Throwable $e) {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    public function getInsertedCount(): int
    {
        return $this->insertedCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /** @return array<int, string> */
    public function getErrors(): array
    {
        return $this->importErrors;
    }

    protected function cell(Collection|array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = data_get($row, $key);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }
}
