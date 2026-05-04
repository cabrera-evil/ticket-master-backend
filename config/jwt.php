<?php

return [
    'secret' => env('JWT_SECRET'),
    'refresh_secret' => env('JWT_REFRESH_SECRET'),
    'ttl' => (int) env('JWT_TTL', 900),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800),
    'cookie_name' => 'jwt',
    'refresh_cookie_name' => 'refreshToken',
];
