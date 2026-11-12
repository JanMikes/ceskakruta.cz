<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

/**
 * An edited order item cannot be saved (unknown product, no price, whole turkey without a weight class).
 * `$itemKey` = index of the row in the edit form, the message is shown on it.
 */
final class OrderItemInvalid extends \Exception
{
    public function __construct(
        readonly public int $itemKey,
        string $message,
    ) {
        parent::__construct($message);
    }
}
