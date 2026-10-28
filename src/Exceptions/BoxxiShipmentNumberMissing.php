<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

/**
 * The order was not sent to Boxxi, or was sent before `boxxi_shipment_number` was stored – the shipment cannot be addressed.
 */
final class BoxxiShipmentNumberMissing extends \Exception
{
}
