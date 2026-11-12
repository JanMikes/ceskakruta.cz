<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Order;

/**
 * What editing an order means for its Boxxi (CoolBalík) shipment.
 */
enum BoxxiEditOutcome
{
    /** Not sent to Boxxi (or never a CoolBalík order) – nothing to do */
    case None;

    /** Still CoolBalík with a known shipment → confirm the recalculated values on the Boxxi form (update) */
    case NeedsUpdate;

    /** Moved away from CoolBalík → the shipment was cancelled, the order is unsent */
    case Cancelled;

    /** Sent before the shipment number was stored → the change must be done in Boxxi by hand */
    case Manual;
}
