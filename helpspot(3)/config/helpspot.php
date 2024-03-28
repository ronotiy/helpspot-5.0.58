<?php

return [
    'mail_cron_interval' => env('MAIL_CRON_INTERVAL', '* * * * *'),
    'maintenance_mode' => env('MAINTENANCE_MODE', false),
    'hosted' => env('hosted', false),
];
