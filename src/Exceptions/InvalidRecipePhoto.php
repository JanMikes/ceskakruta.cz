<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

/**
 * Uploaded recipe photo is not a JPG, GIF or PNG image (the form validates it first).
 */
final class InvalidRecipePhoto extends \InvalidArgumentException
{
}
