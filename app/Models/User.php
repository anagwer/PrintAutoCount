<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles, HasUuids;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

     public function scopeSearch($query, Request $request)
    {
        if ($request->has('find')) {
            $find = $request->find;

            $query->where(function ($query) use ($find) {
                $query->where('username', 'like', '%' . $find . '%')
                    ->orWhere('name', 'like', '%' . $find . '%');
            });
        }

        return $query;
    }

    protected function sortableFields(): array
    {
        return [
            'name',
        ];
    }

    protected function validatedFields($id = null): array
    {
        return [
            'username'          => 'required|string|max:255|unique:users,username' . ($id ? ",$id" : ''),
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email',
            'identity_number'   => 'required|string|unique:users,identity_number' . ($id ? ",$id" : ''),
        ];
    }


}
