<?php

use CeskaKruta\Web\CeskaKrutaKernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new CeskaKrutaKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
