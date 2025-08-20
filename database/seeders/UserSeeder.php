<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'username' => 'admin',
            'password' => bcrypt('admin'),
            'identity_number' => '0',
            'name' => 'Admin',
            'email' => 'admin@localhost',
        ]);

        $user->assignRole('administrator');
    }
}
