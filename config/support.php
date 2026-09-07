<?php

return [
    'email_notifications' => env('SUPPORT_TICKET_EMAIL_ENABLED', env('APP_ENV') === 'production'),
];
