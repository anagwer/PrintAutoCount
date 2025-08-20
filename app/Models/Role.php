<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    use HasUuids;
    protected $guard_name = 'web';
    protected $guarded = [];
    protected $hidden = [
        'created_at', 'updated_at', 'guard_name', 'pivot'
    ];

    public function scopeSearch($query, Request $request){
        if($request->has('find')){
            return $query->where('name', 'like', '%'.$request->find.'%');
        }
        return $query;
    }
}
