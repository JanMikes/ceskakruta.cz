<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use CeskaKruta\Web\Value\Address;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class DeliveryFormData
{
    #[NotBlank]
    #[Regex(
        pattern: '/.*\s\d+.*/',
        message: 'Ulice musí obsahovat číslo popisné.'
    )]
    public string $street = '';

    #[NotBlank]
    public string $city = '';

    #[NotBlank]
    #[Regex(
        pattern: '/^(\d{3} \d{2})$|^(\d{5})$/',
        message: 'Číslo popísné musí být ve formátu "123 45" nebo "12345".'
    )]
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
