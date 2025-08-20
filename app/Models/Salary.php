<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends BaseModel
{
    use HasUuids, HasFactory;

    protected $guarded = [];

    public $incrementing = false;
    protected $keyType = 'string';

    protected function searchableFields(): array
    {
        return ['nik', 'email'];
    }

    protected function sortableFields(): array
    {
        return ['nik', 'email', 'status', 'created_at'];
    }

    protected function validatedFields($id = null): array
    {
        return [
            'nik' => 'required|string|max:30|unique:salaries,nik,' . $id,
            'email' => 'required|email|max:255',
            'status' => 'sometimes|in:0,1,2',
            // 'file_path' => 'nullable|file',
        ];
    }
}
