<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use BcMath\Number;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NumberSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data =[
            [
                'code' => 'SERVICE',
                'name' => 'SERVICE NUMBER',
                'prefix' => 'SRV-',
                'last_number_used' => 1,
                'digit' => 8
            ],
            [
                'code' => 'REPAIR',
                'name' => 'REPAIR NUMBER',
                'prefix' => 'REP-',
                'last_number_used' => 1,
                'digit' => 8
            ],
            [
                'code' => 'VENDOR',
                'name' => 'VENDOR NUMBER',
                'prefix' => 'VEN-',
                'last_number_used' => 1,
                'digit' => 8
            ],
            [
                'code' => 'INSPECT',
                'name' => 'INSPECTION NUMBER',
                'prefix' => 'INS-',
                'last_number_used' => 1,
                'digit' => 8
            ],

        ] ;
        foreach ($data as $item) {
            NumberSequence::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'prefix' => $item['prefix'],
                'last_number_used' => $item['last_number_used'],
                'digit' => $item['digit'],
            ]);
        }
    }
}
