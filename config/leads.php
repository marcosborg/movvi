<?php

return [
    'notification_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('LEAD_NOTIFICATION_EMAILS', 'movvi.pt@gmail.com,adelmotop10@gmail.com'))
    ))),
];
