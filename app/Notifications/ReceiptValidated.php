<?php

namespace App\Notifications;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceiptValidated extends Notification
{
    use Queueable;

    public function __construct(private Receipt $receipt)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recibo validado')
            ->line('O seu recibo foi validado com sucesso.')
            ->line('Valor validado: ' . number_format((float) $this->receipt->verified_value, 2, ',', '.') . ' €.');
    }
}
