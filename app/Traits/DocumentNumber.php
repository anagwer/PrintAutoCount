<?php

namespace App\Traits;

use App\Models\NumberSequence;
use Illuminate\Support\Str;

trait DocumentNumber {

  protected static function generateSequenceNumber($code){
    $number = NumberSequence::where('code', '=', $code)->first();
    $prefix = $number->prefix;
    $countOfDigit = $number->digit;
    $lastUsed = $number->last_number_used;
    // $countOfUsed = Str::len($lastUsed+1);
    $countOfUsed = Str::length($lastUsed + 1);
    $temp = "";
    $next = $lastUsed + 1;

    for ($i=0; $i < $countOfDigit - $countOfUsed; $i++) { 
      $temp = $temp . '0';
    }

    $number->last_number_used = $number->last_number_used + 1;
    $number->save();
    return $prefix.$temp.$next;
  }

}

?>