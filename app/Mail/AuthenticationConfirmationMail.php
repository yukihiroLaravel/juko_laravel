<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Model\Student;

class AuthenticationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Student $student, $code)
    {
        $this->email = $student->email;
        $this->last_name  = $student->last_name;
        $this->first_name = $student->first_name;
        $this->code  = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to($this->email)
        ->subject('認証コードのお知らせです')
        ->view('AuthenticationConfirmationMail')
        ->with(['last_name' => $this->last_name, 'first_name' => $this->first_name, 'code' => $this->code,]);
    }
}
