<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

    public function scopeSearch($query, Request $request){
        if($request->has('find')){
            return $query->where('name', 'like', '%'.$request->find.'%');
        }
        return $query;
    }
}
