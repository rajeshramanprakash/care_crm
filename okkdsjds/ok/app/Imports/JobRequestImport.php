<?php

namespace App\Imports;

use App\Models\JobRequest;
use App\Models\User;
use App\Models\WhatsappMsgGroup;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class JobRequestImport implements ToCollection, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    private $successCount = 0;
    private $errorCount = 0;
    private $importErrors = [];

    public function collection(Collection $rows)
    {
        \Log::info('Starting import', ['total_rows' => $rows->count()]);
        
        foreach ($rows as $index => $row) {
            try {
                \Log::info('Processing row', ['index' => $index, 'row' => $row->toArray()]);
                
                // Validate required fields (date_time is now automatically set)
                if (empty($row['customer_name']) || empty($row['contact_no']) || 
                    empty($row['name']) || empty($row['age']) || empty($row['gender']) || 
                    empty($row['shift']) || empty($row['job_title'])) {
                    $this->errorCount++;
                    $this->importErrors[] = "Row " . ($index + 2) . ": Missing required fields";
                    continue;
                }

                // Set current date and time for all uploaded leads
                $dateTime = Carbon::now();
                \Log::info('Setting current date for lead', ['date_time' => $dateTime->format('Y-m-d H:i:s')]);

                // Find executive by name if provided
                $executiveId = null;
                if (!empty($row['executive_name'])) {
                    $executiveName = trim($row['executive_name']);
                    $nameParts = explode(' ', $executiveName);
                    $firstName = $nameParts[0];
                    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                    
                    $executive = User::where('role_id', 4)
                        ->where('f_name', 'like', '%' . $firstName . '%')
                        ->where('l_name', 'like', '%' . $lastName . '%')
                        ->first();
                    
                    if ($executive) {
                        $executiveId = $executive->id;
                    }
                }

                // Validate gender
                $gender = strtolower(trim($row['gender']));
                if (!in_array($gender, ['male', 'female', 'other'])) {
                    $this->errorCount++;
                    $this->importErrors[] = "Row " . ($index + 2) . ": Invalid gender (must be male, female, or other)";
                    continue;
                }

                // Validate shift
                $shift = trim($row['shift']);
                if (!in_array($shift, ['12', '24', 'both'])) {
                    $this->errorCount++;
                    $this->importErrors[] = "Row " . ($index + 2) . ": Invalid shift (must be 12, 24, or both)";
                    continue;
                }

                // Validate status
                $status = 'active'; // default
                if (!empty($row['status'])) {
                    $statusValue = strtolower(trim($row['status']));
                    if (in_array($statusValue, ['active', 'inactive', 'blacklist'])) {
                        $status = $statusValue;
                    }
                }

                // Prepare data
                $data = [
                    'date_time' => $dateTime,
                    'executive_id' => $executiveId,
                    'customer_name' => trim($row['customer_name']),
                    'contact_no' => trim($row['contact_no']),
                    'name' => trim($row['name']),
                    'age' => trim($row['age']) . '|' . $gender,
                    'expected_salary' => !empty($row['expected_salary']) ? (float)$row['expected_salary'] : null,
                    'shift' => $shift,
                    'total_experience' => !empty($row['total_experience']) ? trim($row['total_experience']) : null,
                    'job_title' => trim($row['job_title']),
                    'other_remark' => !empty($row['other_remark']) ? trim($row['other_remark']) : null,
                    'city' => !empty($row['city']) ? trim($row['city']) : null,
                    'remark' => !empty($row['remark']) ? trim($row['remark']) : null,
                    'status' => $status,
                ];

                \Log::info('Creating job request', ['data' => $data]);

                // Create job request
                $jobRequest = JobRequest::create($data);

                // Handle WhatsApp group
                if ($executiveId) {
                    $number = $data['contact_no'];
                    $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
                    if ($group) {
                        $ids = array_filter(explode(',', $group->executive_ids));
                        if (!in_array($executiveId, $ids)) {
                            $ids[] = $executiveId;
                            $group->executive_ids = implode(',', $ids);
                            $group->save();
                        }
                    } else {
                        WhatsappMsgGroup::create([
                            'whatsapp_number' => $number,
                            'executive_ids' => $executiveId,
                        ]);
                    }
                }

                $this->successCount++;
                \Log::info('Job request created successfully', ['id' => $jobRequest->id]);

            } catch (\Exception $e) {
                $this->errorCount++;
                $this->importErrors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                \Log::error('Error processing row', ['index' => $index, 'error' => $e->getMessage()]);
            }
        }
        
        \Log::info('Import completed', ['success_count' => $this->successCount, 'error_count' => $this->errorCount]);
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getErrorCount()
    {
        return $this->errorCount;
    }

    public function getErrors()
    {
        return $this->importErrors;
    }
}