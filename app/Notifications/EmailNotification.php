<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Ichtrojan\Otp\Otp  AS OTP;
use Ichtrojan\Otp\Models\Otp as ModelsOtp;
class EmailNotification extends Notification
{
    use Queueable;
    public $message;
    public $subject;
    public $fromEmail;
    public $mailer;
    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
        $this->message = 'Use the verification code to confirm your email';
        $this->subject='Verification needed';
        $this->fromEmail=env('MAIL_FROM_NAME');
        $this->mailer= env('mailer');
        $this->otp = new OTP;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
       $token = $this->otp->generate($notifiable->email,'numeric');

       
        return (new MailMessage)
            ->mailer(env('MAIL_MAILER'))
            ->subject($this->subject)
            ->greeting('Hello,'.$notifiable->first_name)
            ->line($this->message)
            ->line('code :'.$token->token);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
