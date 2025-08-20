<?php

namespace App\Imports;

use App\Models\AttendanceLocation;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AttendanceLocationsImport implements ToCollection, WithHeadingRow
{
    public $failedRows = [];
    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (!isset($row['nama_area']) || !isset($row['titik_lokasi'])) {
                    throw new \Exception("Field 'nama_area' or 'titik_lokasi' empty in $rowNumber");
                }

                $locationParts = explode(',', $row['titik_lokasi']);

                if (count($locationParts) !== 2) {
                    throw new \Exception("'titik location' in row $rowNumber (must be 'lat,long')");
                }

                [$lat, $lng] = array_map('trim', $locationParts);

                if (!is_numeric($lat) || !is_numeric($lng)) {
                    throw new \Exception("Latitude or Longitude not valid in $rowNumber");
                }

                AttendanceLocation::updateOrCreate(
                    ['name' => $row['nama_area']],
                    [
                        'latitude'  => round((float) $lat, 8),
                        'longitude' => round((float) $lng, 8),
                        'radius'    => 50
                    ]
                );
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->failedRows[] = [
                'row'   => $rowNumber ?? '-',
                'error' => $e->getMessage(),
            ];
            throw $e;
        }
    }
}
