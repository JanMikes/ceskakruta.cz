<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OrderNotFound extends NotFoundHttpException
{
}
