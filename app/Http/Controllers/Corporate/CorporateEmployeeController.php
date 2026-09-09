<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Concerns\ManagesCorporateEmployeeRecords;
use App\Http\Controllers\Controller;
use App\Models\CorporateEmployee;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorporateEmployeeController extends Controller
{
    use ManagesCorporateEmployeeRecords;

    private function requireCorporate()
    {
        $corporate = $this->currentCorporate();
        if (! $corporate || ! $corporate->is_active) {
            return redirect()->route('corporate.login')->with('error', 'Session expired. Please login again.');
        }

        return $corporate;
    }

    public function index()
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        $items = $this->employeeQuery($corporate)->orderByDesc('id')->get();

        return view('corporate.employees.index', [
            'items' => $items,
            'corporate' => $corporate,
            'employee_login_url' => url('/corporate/employee/login'),
        ]);
    }

    public function create()
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        return view('corporate.employees.form', [
            'item' => new CorporateEmployee(['is_active' => true]),
            'corporate' => $corporate,
        ]);
    }

    public function store(Request $request)
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        $validated = $this->validateEmployeePayload($request, $corporate);
        $validated['corporate_user_id'] = $corporate->id;
        $filePath = $this->storePolicyFile($request);
        if ($filePath) {
            $validated['policy_terms_file'] = $filePath;
        }
        CorporateEmployee::create($validated);

        return redirect()->route('corporate.employees.index')
            ->with('success', 'Employee added. Login: /corporate/employee/login — use corporate username, employee ID and password.');
    }

    public function edit(CorporateEmployee $employee)
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        $item = $this->findOwnedEmployee($corporate, $employee->id);

        return view('corporate.employees.form', [
            'item' => $item,
            'corporate' => $corporate,
        ]);
    }

    public function update(Request $request, CorporateEmployee $employee)
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        $item = $this->findOwnedEmployee($corporate, $employee->id);
        $validated = $this->validateEmployeePayload($request, $corporate, $item->id);
        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        $filePath = $this->storePolicyFile($request, $item);
        if ($filePath) {
            $validated['policy_terms_file'] = $filePath;
        }
        $item->update($validated);

        return redirect()->route('corporate.employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(CorporateEmployee $employee)
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        $item = $this->findOwnedEmployee($corporate, $employee->id);
        if ($item->policy_terms_file) {
            \Storage::disk('public')->delete($item->policy_terms_file);
        }
        $item->delete();

        return redirect()->route('corporate.employees.index')->with('success', 'Employee removed.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            abort(403);
        }

        $headers = self::bulkCsvHeaders();
        $callback = function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, [
                'EMP001', 'John Doe', '1990-05-15', '9876543210', 'Male',
                'john@example.com', 'Self', '2024-01-01', '', '500000',
                '2024-01-01', '2025-12-31', '5000', 'ChangeMe123',
            ]);
            fclose($out);
        };

        return response()->streamDownload($callback, 'corporate-employees-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function importBulk(Request $request)
    {
        $corporate = $this->requireCorporate();
        if ($corporate instanceof \Illuminate\Http\RedirectResponse) {
            return $corporate;
        }

        $request->validate([
            'bulk_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $rows = [];
        if (($handle = fopen($request->file('bulk_file')->getRealPath(), 'r')) !== false) {
            $header = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);
            while (($row = fgetcsv($handle)) !== false) {
                if (! $header || count($header) !== count($row)) {
                    continue;
                }
                $rows[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        $inserted = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $data = $this->employeeRowFromCsv($row, $corporate);
            if (! $data) {
                $skipped++;

                continue;
            }
            if ($this->employeeQuery($corporate)->where('employee_id', $data['employee_id'])->exists()) {
                $skipped++;

                continue;
            }
            CorporateEmployee::create($data);
            $inserted++;
        }

        $msg = "{$inserted} employee(s) imported.";
        if ($skipped > 0) {
            $msg .= " {$skipped} row(s) skipped (duplicate or invalid). Upload Policy Terms manually for each employee.";
        }

        return redirect()->route('corporate.employees.index')->with('success', $msg);
    }
}
