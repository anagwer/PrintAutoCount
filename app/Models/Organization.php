<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends BaseModel
{
    use HasFactory, HasUuids;
    protected $guarded = [];

    protected function searchableFields(): array
    {
        return ['name'];
    }
    protected function validatedFields($id = null): array
    {
        return [
            'name'                      => 'required|max:255',
            'phone_number'              => 'required|max:30',
            'alternate_phone_number'    => 'required|max:30',
        ];
    }
}
