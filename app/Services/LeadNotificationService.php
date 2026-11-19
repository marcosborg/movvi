<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class LeadNotificationService
{
    public function send(Model $lead, Notification $notification): bool
    {
        $email = config('leads.notification_email');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Lead guardado sem notificação: destinatário inválido.', [
                'lead_type' => get_class($lead),
                'lead_id' => $lead->getKey(),
            ]);

            return false;
        }

        try {
            NotificationFacade::route('mail', $email)->notify($notification);

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Lead guardado, mas não foi possível enviar a notificação.', [
                'lead_type' => get_class($lead),
                'lead_id' => $lead->getKey(),
                'exception_type' => get_class($exception),
            ]);

            return false;
        }
    }
}
