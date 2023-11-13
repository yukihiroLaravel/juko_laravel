<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Model\Student;

class AuthenticationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    private string $email;
    private string $fullName;
    private string $code;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        Student $student,
        string $code,
        string $token)
    {
        $this->email     = $student->email;
        $this->fullName  = $student->fullName;
        $this->code      = $code;
        $this->token     = $token;
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
        ->with([
            'fullName' => $this->fullName,
            'code' => $this->code,
            'token' => $this->token,
        ]);
    }
}
