<?php

declare(strict_types=1);

use CeskaKruta\Web\Tests\Support\TestDatabase;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Fresh database for every run; DAMA then rolls back every single test inside a transaction
$databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
assert(is_string($databaseUrl));
TestDatabase::recreate($databaseUrl);
