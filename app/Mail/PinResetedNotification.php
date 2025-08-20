<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PinResetedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $employeeName;
    public $newPin;
    public $recoveryKey;
    public $resetAt;

    /**
     * Create a new message instance.
     *
     * @param string $employeeName
     * @param string $newPin
     * @param string $recoveryKey
     * @param string $resetAt
     * @return void
     */
    public function __construct($employeeName, $newPin, $recoveryKey, $resetAt)
    {
        $this->employeeName = $employeeName;
        $this->newPin = $newPin;
        $this->recoveryKey = $recoveryKey;
        $this->resetAt = $resetAt;
    }

    /**
     * Build the message.
     *
     * @return \Illuminate\Mail\Mailable
     */
    public function build()
    {
        return $this->subject('PIN Anda Telah Direset')
                    ->view('emails.pin_reseted');
    }
}
