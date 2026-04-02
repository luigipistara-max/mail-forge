<?php

declare(strict_types=1);

return [
    'default_batch_size'        => (int) ($_ENV['MAIL_BATCH_SIZE']     ?? getenv('MAIL_BATCH_SIZE')     ?: 100),
    'default_batch_interval'    => (int) ($_ENV['MAIL_BATCH_INTERVAL'] ?? getenv('MAIL_BATCH_INTERVAL') ?: 10),
    'max_retries'               => 3,
    'retry_delay_seconds'       => 300,
    'lock_timeout_seconds'      => 600,
    'max_failures_before_pause' => 50,
];
