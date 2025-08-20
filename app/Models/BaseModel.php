<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasFactory;

    protected function searchableFields(): array{
        return []; 
    }

    protected function sortableFields(): array{
        return [];
    }

    protected function validatedFields($id = null):array{
        return [];
    }

    public function scopeSearch(Builder $query, array $search): Builder{
        if (!empty($search['find'])) {
            $query->where(function ($q) use ($search) {
                $searchableFields = $this->searchableFields();

                foreach ($searchableFields as $field) {
                    // Jika field berisi titik (.), berarti field ini berada dalam relasi
                    if (str_contains($field, '.')) {
                        [$relation, $relatedField] = explode('.', $field, 2);

                        // Memastikan relasi tersebut ada di model
                        if (method_exists($this, $relation)) {
                            $q->orWhereHas($relation, function ($relationQuery) use ($relatedField, $search) {
                                $relationQuery->where($relatedField, 'like', '%' . $search['find'] . '%');
                            });
                        }
                    } else {
                        // Pencarian langsung di field model jika tidak ada relasi
                        $q->orWhere($field, 'like', '%' . $search['find'] . '%');
                    }
                }
            });
        }

        return $query;
    }

    public function scopeFilter(Builder $query,  array $filters):Builder{
        if(!empty($filters)){
            foreach ($filters as $key => $value) {
                $value !== null ?
                $query->where($key, $value) :$query;
            }
        }

        return $query;
    }

    public function scopeSort(Builder $query, string $sortField, string $sortOrder): Builder{
        $validFields = $this->sortableFields();

        if (in_array($sortField, $validFields)) {
            return $query->orderBy($sortField, $sortOrder);
        }

        return $query;
    }

    

}
