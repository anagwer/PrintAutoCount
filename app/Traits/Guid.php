<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Ramsey\Uuid\Rfc4122\UuidV4;

trait Guid{

  protected static function bootGuid(){
    static::creating(function ($model) {
      $model->guid = UuidV4::uuid4()->toString();
        // if (Auth::check()) {
        // }
    });
  }
}
?>