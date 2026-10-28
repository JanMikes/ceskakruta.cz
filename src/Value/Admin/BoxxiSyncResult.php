<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * What `SendOrderToBoxxi` did with the shipment after saving the form values on the order.
 */
enum BoxxiSyncResult
{
    /** New shipment created (`POST /v1/shipments`) */
    case Created;

    /** Existing shipment updated (`POST /v1/shipments/{shipmentNumber}/update`) */
    case Updated;

    /** Already sent, but its shipment number is unknown (sent before it was stored) → Boxxi not touched */
    case NotSent;
}
