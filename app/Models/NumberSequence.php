<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberSequence extends BaseModel
{
    use HasFactory;
    use HasUuids;
    protected $guarded = [''];

    protected function sortableFields(): array
    {
        return [
            'name',
            'prefix',
            'created_at',
        ];
    }

    protected function searchableFields(): array
    {
        return [
            'name',
            'prefix',
        ];
    }
}
