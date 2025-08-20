<?php

namespace App\Imports;

use App\Models\JobPosition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class JobPositionImport implements ToCollection, WithHeadingRow
{

    public $failedRows = [];

    public function collection(Collection $rows)
    {
        $this->failedRows = [];
        $jobPositionsInFile = [];
        $jobPositionsNames = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;

            $jobPositionName = trim($row['name'] ?? '');

            if (empty($jobPositionName)) {
                $this->failedRows[] = [
                    'row' => $excelRow,
                    'error' => "Row 'name' empty",
                ];
                continue;
            }

            if (in_array($jobPositionName, $jobPositionsInFile)) {
                $this->failedRows[] = [
                    'row' => $excelRow,
                    'error' => "Duplicate job position '$jobPositionName' in file",
                ];
                continue;
            }

            $jobPositionsInFile[] = $jobPositionName;
            $jobPositionsNames[$excelRow] = $jobPositionName;
        }

        if (count($this->failedRows)) {

            return;
        }

        $existingNames = JobPosition::whereIn('name', $jobPositionsInFile)->pluck('name')->toArray();

        foreach ($jobPositionsNames as $rowNum => $name) {
            if (in_array($name, $existingNames)) {
                $this->failedRows[] = [
                    'row' => $rowNum,
                    'error' => "Job position '$name' already exists",
                ];
            }
        }

        if (count($this->failedRows)) {
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($jobPositionsNames as $rowNum => $name) {
                $desc = $rows[$rowNum - 2]['description'] ?? null;
                JobPosition::create([
                    'name' => $name,
                    'description' => $desc,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->failedRows[] = [
                'row' => 'N/A',
                'error' => "Failed to import job positions: " . $e->getMessage(),
            ];
        }
    }
}
