<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PinCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $employeeName;
    public $pin;
    public $recoveryKey;
    public $createdAt;

    public function __construct($employeeName, $pin, $recoveryKey, $createdAt)
    {
        $this->employeeName = $employeeName;
        $this->pin = $pin;
        $this->recoveryKey = $recoveryKey;
        $this->createdAt = $createdAt;
    }

    public function build()
    {
        return $this->subject('PIN dan Recovery Key Anda')
            ->view('emails.pin_created');
    }
}

