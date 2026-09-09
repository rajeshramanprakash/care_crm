<?php

namespace App\Imports;

use App\Models\B2BUser;
use App\Services\B2BCorporateLeadService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class B2BCorporateLeadBulkImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private int $insertedCount = 0;

    private int $errorCount = 0;

    /** @var array<int, string> */
    private array $importErrors = [];

    public function __construct(
        protected B2BUser $b2bUser,
        protected B2BCorporateLeadService $leadService
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $number = $this->cell($row, ['number', 'mobile', 'contact_no', 'phone']);
            $service = $this->cell($row, ['service', 'service_requirement']);
            $detail = $this->cell($row, ['detail', 'details', 'detail_box', 'query_remark', 'remark']);
            $bulkRaw = $this->cell($row, ['bulk', 'bulk_qty', 'quantity', 'qty']);

            if ($service === '' && $detail === '' && $bulkRaw === '' && $number === '') {
                continue;
            }

            if ($service === '' || $detail === '' || $bulkRaw === '' || (int) $bulkRaw < 1) {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: service, detail, and bulk (min 1) are required.";

                continue;
            }

            if ($number !== '' && ! preg_match('/^[0-9]{10}$/', preg_replace('/\D+/', '', $number))) {
                $this->errorCount++;
                $this->importErrors[] = "Row {$rowNumber}: number must be 10 digits when provided.";

                continue;
            }

            try {
                $this->leadService->createLead($this->b2bUser, [
                    'number' => $number !== '' ? preg_replace('/\D+/', '', $number) : null,
                    'service' => $service,
                    'detail' => $detail,
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
