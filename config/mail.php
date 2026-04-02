<?php

declare(strict_types=1);

return [
    'driver'          => $_ENV['MAIL_DRIVER']         ?? getenv('MAIL_DRIVER')         ?: 'smtp',
    'host'            => $_ENV['MAIL_HOST']           ?? getenv('MAIL_HOST')           ?: 'smtp.example.com',
    'port'            => (int) ($_ENV['MAIL_PORT']    ?? getenv('MAIL_PORT')           ?: 587),
    'username'        => $_ENV['MAIL_USERNAME']       ?? getenv('MAIL_USERNAME')       ?: '',
    'password'        => $_ENV['MAIL_PASSWORD']       ?? getenv('MAIL_PASSWORD')       ?: '',
    'encryption'      => $_ENV['MAIL_ENCRYPTION']     ?? getenv('MAIL_ENCRYPTION')     ?: 'tls',
    'from_address'    => $_ENV['MAIL_FROM_ADDRESS']   ?? getenv('MAIL_FROM_ADDRESS')   ?: 'noreply@example.com',
    'from_name'       => $_ENV['MAIL_FROM_NAME']      ?? getenv('MAIL_FROM_NAME')      ?: 'Mail Forge',
    'reply_to_address'=> $_ENV['MAIL_REPLYTO_ADDRESS'] ?? getenv('MAIL_REPLYTO_ADDRESS') ?: '',
    'reply_to_name'   => $_ENV['MAIL_REPLYTO_NAME']  ?? getenv('MAIL_REPLYTO_NAME')   ?: '',
    'timeout'         => (int) ($_ENV['MAIL_TIMEOUT'] ?? getenv('MAIL_TIMEOUT')        ?: 30),
    'batch_size'      => (int) ($_ENV['MAIL_BATCH_SIZE']     ?? getenv('MAIL_BATCH_SIZE')     ?: 100),
    'batch_interval'  => (int) ($_ENV['MAIL_BATCH_INTERVAL'] ?? getenv('MAIL_BATCH_INTERVAL') ?: 10),
];
