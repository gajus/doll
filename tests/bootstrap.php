<?php
require __DIR__ . '/../vendor/autoload.php';

$_ENV['dsn'] = [];

foreach (['host', 'driver', 'database', 'user', 'password'] as $var_name) {
    if (isset($_ENV[$var_name])) {
        $_ENV['dsn'][$var_name] = $_ENV[$var_name];
    }
}
