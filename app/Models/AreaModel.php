<?php

namespace App\Models;

use App\Models\BaseModel;

class Area extends BaseModel
{
    protected $table = 'AREA';
    protected $guarded = [];
    public $timestamps = false;

    protected function searchableFields(): array
    {
        return ['AreaCode', 'Description'];
    }

    protected function validatedFields($id = null): array
    {
        return [];
    }

    protected function sortableFields(): array
    {
        return ['AreaCode', 'Description'];
    }
}
