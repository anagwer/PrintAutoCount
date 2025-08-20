<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PinUpdatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $employeeName;
    public $newPin;
    public $recoveryKey;
    public $updatedAt;

    /**
     * Create a new message instance.
     *
     * @param string $employeeName
     * @param string $newPin
     * @param string $recoveryKey
     * @param string $updatedAt
     * @return void
     */
    public function __construct($employeeName, $newPin, $recoveryKey, $updatedAt)
    {
        $this->employeeName = $employeeName;
        $this->newPin = $newPin;
        $this->recoveryKey = $recoveryKey;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Build the message.
     *
     * @return \Illuminate\Mail\Mailable
     */
    public function build()
    {
        return $this->subject('PIN Anda Telah Diperbarui')
                    ->view('emails.pin_updated');
    }
}
