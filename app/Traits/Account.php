<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Account{

  protected static function bootAccount(){
    static::creating(function ($model) {
        if (Auth::check()) {
            $model->created_by = Auth::id();
        }
    });

    static::updating(function ($model) {
        if (Auth::check()) {
            $model->updated_by = Auth::id();
        }
    });

    static::deleting(function ($model) {
            if (
                Auth::check() &&
                in_array(SoftDeletes::class, class_uses_recursive(get_class($model)))
            ) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
  }
}
?>
