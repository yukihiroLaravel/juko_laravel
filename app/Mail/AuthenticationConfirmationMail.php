<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AuthenticationConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private string $email;

    private string $fullName;

    private string $code;

    private string $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $email,
        string $fullName,
        string $code,
        string $token
    ) {
        $this->email = $email;
        $this->fullName = $fullName;
        $this->code = $code;
        $this->token = $token;
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
