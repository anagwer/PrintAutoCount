<?php

namespace App\Traits;

trait SequenceNumberTrait{
  public function generateSequenceNumber(String $code, String $organizationId = null) : string{
    $orgId = $organizationId ? $organizationId : null;
    return $orgId;
  }
}