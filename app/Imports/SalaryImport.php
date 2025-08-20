<?php

namespace App\Imports;

use App\Models\Salary;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SalaryImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $filePath = null;
        if (!empty($row['file_path'])) {
            $sourcePath = $row['file_path'];

            if (file_exists($sourcePath)) {
                $fileName = time() . '_' . basename($sourcePath);
                $destinationPath = 'public/files-salaries/' . $fileName;

                Storage::put($destinationPath, file_get_contents($sourcePath));

                $filePath = $destinationPath;
            } else {
                return null;
            }
        }

        return new Salary([
            'nik' => $row['nik'],
            'email' => $row['email'],
            'status' => 0,
            'file_path' => $filePath,
        ]);
    }
}
