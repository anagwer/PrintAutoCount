<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\JobLevel;
use App\Models\JobPosition;
use App\Models\Organization;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToCollection, WithHeadingRow
{
    public $failedRows = [];
    public $idEmployee = [];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $identityNumbers = [];
            $emails = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

               $employmentStatus = EmploymentStatus::where('name', $row['employment_status'])->first();
                $organization = Organization::where('name', $row['organization'])->first();
                $jobLevel = JobLevel::where('name', $row['job_level'])->first();
                $jobPosition = JobPosition::where('name', $row['job_position'])->first();

                if (!$employmentStatus) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => 'EmploymentStatus not found',
                    ];
                    continue;
                }
                if (!$organization) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => 'Organization not found',
                    ];
                    continue;
                }
                if (!$jobLevel) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => 'JobLevel not found',
                    ];
                    continue;
                }
                if (!$jobPosition) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => 'JobPosition not found',
                    ];
                    continue;
                }

                if (in_array($row['identity_number'], $identityNumbers)) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => "Duplicate identity_number '{$row['identity_number']}' in file",
                    ];
                    continue;
                }
                $identityNumbers[] = $row['identity_number'];

                if (Employee::where('identity_number', $row['identity_number'])->exists()) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => "identity_number '{$row['identity_number']}' already exists",
                    ];
                    continue;
                }

                if (empty($row['email'])) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => "Email is required",
                    ];
                    continue;
                }
                                if (in_array(strtolower($row['email']), $emails)) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => "Duplicate email '{$row['email']}' in file",
                    ];
                    continue;
                }
                $emails[] = strtolower($row['email']);

                if (Employee::where('email', $row['email'])->exists()) {
                    $this->failedRows[] = [
                        'row' => $rowNumber,
                        'error' => "Email '{$row['email']}' already exists in database",
                    ];
                    continue;
                }

                $employee = Employee::create([
                    'identity_number'           => $row['identity_number'],
                    'badge_number'              => $row['badge_number'],
                    'name'                      => $row['name'],
                    'gender'                    => $row['gender'],
                    'phone_number'              => $row['phone_number'],
                    'alternate_phone_number'    => $row['alternate_phone_number'],
                    'email'                     => $row['email'],
                    'employment_status_id'      => $employmentStatus->id,
                    'organization_id'           => $organization->id,
                    'job_level'                 => $jobLevel->id,
                    'job_position'              => $jobPosition->id,
                ]);

                $user = User::create([
                            'username'      => $employee->identity_number,
                            'password'      => Hash::make('Lbg@1080*'),
                            'employee_id'   => $employee->id,
                        ]);
                $user->assignRole('Karyawan Dasar');

                $this->idEmployee[] = $employee->id;
            }

            if (count($this->failedRows)) {
                DB::rollBack();
                $errorMessages = collect($this->failedRows)->map(fn($e) => "Row {$e['row']}: {$e['error']}")->implode("; ");
                throw new \Exception("Failed to import data: {$errorMessages}");
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}
