<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;

class EmailRecipient
{
    use Notifiable;

    protected $email;

    /**
     * Create a new instance of EmailRecipient.
     *
     * @param string $email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail(): string
    {
        // This method provides the email address for the notification
        
        return $this->email;
    }
}
