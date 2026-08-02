<?php

return [
    'driver' => env('MAIL_MAILER', env('MAIL_DRIVER', 'smtp')),

    'host' => env('MAIL_HOST', 'smtp.gmail.com'),

    'port' => env('MAIL_PORT', 587),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'your-email@example.com'),
        'name' => env('MAIL_FROM_NAME', 'YourAppName'),
    ],

    'encryption' => env('MAIL_ENCRYPTION', 'tls'),

    'username' => env('MAIL_USERNAME'),

    'password' => env('MAIL_PASSWORD'),

    'sendmail' => '/usr/sbin/sendmail -bs',
'markdown' => [
        'theme' => 'default',
        'paths' => [
                resource_path('views/vendor/mail'),
        ],
    ],
];






