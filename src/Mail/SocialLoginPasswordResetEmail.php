<?php

namespace Wyzo\GraphQLAPI\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SocialLoginPasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new mailable instance.
     *
     * @param  array  $verificationData
     * @return void
     */
    public function __construct(public $data) {}

    /**
     * Build the message.
     *
     * @return \Illuminate\View\View
     */
    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($this->data['email'])
            ->subject(trans('wyzo_graphql::app.mail.customer.password.reset'))
            ->view('wyzo_graphql::shop.emails.customer.password-reset-email')
            ->with('data', $this->data);
    }
}
