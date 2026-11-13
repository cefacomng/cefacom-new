<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** Sent to the NEW address so a customer can only switch to an address they control. */
class VerifyNewEmail extends Notification
{
    public function __construct(private readonly int $userId, private readonly string $newEmail) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute('email.change.verify', now()->addMinutes(60), [
            'id' => $this->userId,
            'hash' => sha1($this->newEmail),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new Cefacom email address')
            ->line('You asked to use this email address for your Cefacom account.')
            ->action('Confirm email address', $this->verificationUrl())
            ->line('This link expires in 60 minutes. If you did not ask for this, ignore this email and nothing will change.');
    }
}
