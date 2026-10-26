<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

/**
 * Package sizes input ("500, 1000") contains something else than positive integers (grams).
 */
final class InvalidPackagesSize extends \InvalidArgumentException
{
}
