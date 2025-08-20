<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Salary;

class SalaryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $salary;

    public function __construct(Salary $salary)
    {
        $this->salary = $salary;
    }

    public function build()
    {
        $filePath = storage_path('app/' . $this->salary->file_path);
        $fileName = basename($this->salary->file_path);

        return $this->subject('Bukti Gaji Anda')
            ->view('emails.salary_notification')
            ->attach($filePath, [
                'as' => $fileName,
                'mime' => mime_content_type($filePath),
            ]);
    }
}
