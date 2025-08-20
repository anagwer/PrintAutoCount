<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SalaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = time() + rand(1, 99) . '_' . Str::random(3) . 'salaries.jpg';
        $path = 'public/images/' . $fileName;

        return [
            'id' => Str::uuid(),
            'nik' => 'NIK' . rand(1000, 9999),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => rand(0, 2),
            'file_path' => $path,
        ];
    }
}
