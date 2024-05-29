<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use CeskaKruta\Web\Value\Address;

final class DeliveryFormData
{
    public string $street = '';
    public string $city = '';
    public string $postalCode = '';

    public static function fromAddress(null|Address $address): self
    {
        $self = new self();

        if ($address !== null) {
            $self->street = $address->street;
            $self->city = $address->city;
            $self->postalCode = $address->postalCode;
        }

        return $self;
    }
}
