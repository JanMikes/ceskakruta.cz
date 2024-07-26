<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class UserNotRegistered extends UserNotFoundException
{
}
