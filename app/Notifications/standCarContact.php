<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class standCarContact extends Notification
{
    use Queueable;

    private $TransferForm;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($TransferForm)
    {
        $this->TransferForm = $TransferForm;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Novo pedido de viatura no stand Movvi')
                    ->replyTo($this->TransferForm->email, $this->TransferForm->name)
                    ->line('Existe um novo pedido no Stand.')
                    ->action('Ir para o pedido', route('admin.stand-car-forms.show', $this->TransferForm))
                    ->line('Nome: ' . $this->TransferForm->name)
                    ->line('Email: ' . $this->TransferForm->email)
                    ->line('Telefone: ' . $this->TransferForm->phone);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
