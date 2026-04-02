<?php

declare(strict_types=1);

return [
    'csrf_token_length'         => 32,
    'session_regenerate'        => true,
    'max_login_attempts'        => 5,
    'lockout_minutes'           => 15,
    'password_min_length'       => 8,
    'password_require_uppercase'=> true,
    'password_require_number'   => true,
    'rate_limit_window'         => 60,
    'rate_limit_max'            => 100,
];
