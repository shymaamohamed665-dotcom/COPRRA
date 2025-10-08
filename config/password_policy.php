<?php

return [
    'min_length' => 10,
    'max_length' => 128,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => false,
    'forbidden_passwords' => [
        0 => 'password',
        1 => '123456',
        2 => 'qwerty',
        3 => 'abc123',
        4 => 'password123',
        5 => 'admin',
        6 => 'root',
        7 => 'user',
    ],
    'history_count' => 5,
    'expiry_days' => 90,
    'lockout_attempts' => 5,
    'lockout_duration' => 30,
];
