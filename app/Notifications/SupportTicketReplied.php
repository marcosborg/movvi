<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReplied extends Notification
{
    public function __construct(public SupportTicket $ticket)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Movvi — Nova resposta no ticket '.$this->ticket->number)
            ->greeting('Olá!')
            ->line('A equipa de suporte respondeu ao seu ticket '.$this->ticket->number.'.')
            ->line('Consulte a conversa para ler a resposta e enviar a informação solicitada.')
            ->action('Ver e responder ao ticket', route('admin.support-tickets.show', $this->ticket))
            ->salutation('Equipa Movvi');
    }
}
