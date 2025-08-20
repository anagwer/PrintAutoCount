<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data = ([
            [
                'name' => 'LIDAH BUAYA GROUP',
                'address' => 'MAGELANG',
            ],
        ]);

        foreach ($data as $item) {
            Organization::create([
                'name' => $item['name'],
                'address' => $item['address'],
            ]);
        }

    }
}
