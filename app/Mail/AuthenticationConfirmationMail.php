<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AuthenticationConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private string $email,
        private string $fullName,
        private string $code,
        private string $token
    ) {}

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
