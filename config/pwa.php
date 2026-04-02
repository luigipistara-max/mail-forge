<?php

declare(strict_types=1);

return [
    'name'        => $_ENV['PWA_NAME']       ?? getenv('PWA_NAME')       ?: 'Mail Forge',
    'short_name'  => $_ENV['PWA_SHORT_NAME'] ?? getenv('PWA_SHORT_NAME') ?: 'MailForge',
    'theme_color' => $_ENV['PWA_THEME_COLOR'] ?? getenv('PWA_THEME_COLOR') ?: '#1a73e8',
    'bg_color'    => $_ENV['PWA_BG_COLOR']   ?? getenv('PWA_BG_COLOR')   ?: '#ffffff',
    'display'     => 'standalone',
    'orientation' => 'portrait',
    'start_url'   => '/',
    'icons'       => [
        [
            'src'   => '/assets/icons/icon-72x72.png',
            'sizes' => '72x72',
            'type'  => 'image/png',
        ],
        [
            'src'   => '/assets/icons/icon-96x96.png',
            'sizes' => '96x96',
            'type'  => 'image/png',
        ],
        [
            'src'   => '/assets/icons/icon-128x128.png',
            'sizes' => '128x128',
            'type'  => 'image/png',
        ],
        [
            'src'   => '/assets/icons/icon-144x144.png',
            'sizes' => '144x144',
            'type'  => 'image/png',
        ],
        [
            'src'   => '/assets/icons/icon-152x152.png',
            'sizes' => '152x152',
            'type'  => 'image/png',
        ],
        [
            'src'   => '/assets/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type'  => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src'   => '/assets/icons/icon-384x384.png',
            'sizes' => '384x384',
            'type'  => 'image/png',
        ],
        [
            'src'   => '/assets/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type'  => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
];
