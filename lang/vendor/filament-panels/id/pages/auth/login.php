<?php

return [
    'title' => 'Masuk',
    'heading' => 'Masuk ke E-Surat BPRS Sragen',
    'actions' => [
        'register' => ['before' => '', 'label' => ''],
        'request_password_reset' => ['label' => 'Lupa kata sandi?'],
    ],
    'form' => [
        'email' => ['label' => 'Alamat email'],
        'password' => ['label' => 'Kata sandi'],
        'remember' => ['label' => 'Ingat saya'],
        'actions' => [
            'authenticate' => ['label' => 'Masuk'],
        ],
    ],
    'messages' => [
        'failed' => 'Email atau kata sandi tidak cocok.',
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'Terlalu banyak percobaan masuk',
            'body' => 'Silakan coba lagi dalam :seconds detik.',
        ],
    ],
];
