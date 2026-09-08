<?php

return [
    'production_image_key' => env('PRODUCTION_IMAGE_READ_KEY'),
    'production_image_ip' => env('PRODUCTION_IMAGE_READ_IP'),
    'email_notifications' => env('SUPPORT_TICKET_EMAIL_ENABLED', env('APP_ENV') === 'production'),
];
