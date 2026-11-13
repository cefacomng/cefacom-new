<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the CURRENT address so an account takeover attempt is visible to the real owner. */
class EmailChangeRequested extends Notification
{
    public function __construct(private readonly string $newEmail) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A change of your Cefacom email address was requested')
            ->line('Someone asked to change the email address on your Cefacom account to '.self::mask($this->newEmail).'.')
            ->line('Your address stays the same until the new address is confirmed. If this was not you, change your password now.');
    }
}
