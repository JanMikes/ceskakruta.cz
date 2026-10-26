<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Support;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;

abstract class WebTestCase extends SymfonyWebTestCase
{
    use RestoresExceptionHandler;
}
