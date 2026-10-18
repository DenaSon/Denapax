<?php

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/admin',
    'prefix' => '',
    'domain' => null,
    'views' => true,
    'limiters' => ['login' => 'login', 'two-factor' => 'two-factor'],
    'features' => [], // No public staff registration or password reset.
];
