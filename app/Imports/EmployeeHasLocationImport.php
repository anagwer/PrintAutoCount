<?php

namespace App\Imports;

use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\EmployeeHasLocation;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeHasLocationImport implements ToCollection, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public $failedRows = [];
    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $employee = Employee::where('identity_number', $row['employee_id'])->first();
                $location = AttendanceLocation::where('name', $row['location_setting_name'])->first();

                if (!$employee || !$location) {
                    $this->failedRows[] = [
                        'row' => $index + 2,
                        'employee_id' => $row['employee_id'],
                        'location_setting_name' => $row['location_setting_name'],
                        'error' => !$employee ? 'Employee not found' : 'Location not found',
                    ];
                    throw new Exception("Error on row " . ($index + 2) . ": " . end($this->failedRows)['error']);
                }

                EmployeeHasLocation::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'attendance_location_id' => $location->id
                    ],
                    []
                );
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
    }
}
