<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use CeskaKruta\Web\Value\User;
use Symfony\Component\Validator\Constraints\Regex;

final class UserInfoFormData
{
    public null|string $name = null;
    public null|int $preferredPlaceId = null;
    public null|string $phone = null;
    #[Regex(
        pattern: '/.*\s\d+.*/',
        message: 'Ulice musí obsahovat číslo popisné.'
    )]
    public null|string $deliveryStreet = null;
    public null|string $deliveryCity = null;
    #[Regex(
        pattern: '/^(\d{3} \d{2})$|^(\d{5})$/',
        message: 'Číslo popísné musí být ve formátu "123 45" nebo "12345".'
    )]
    public null|string $deliveryZip = null;
    public bool $companyInvoicing = false;
    public null|string $companyName = null;
    public null|string $companyId = null;
    public null|string $companyVatId = null;
    #[Regex(
        pattern: '/.*\s\d+.*/',
        message: 'Ulice musí obsahovat číslo popisné.'
    )]
    public null|string $invoicingStreet = null;
    public null|string $invoicingCity = null;
    #[Regex(
        pattern: '/^(\d{3} \d{2})$|^(\d{5})$/',
        message: 'Číslo popísné musí být ve formátu "123 45" nebo "12345".'
    )]
    public null|string $invoicingZip = null;

    public static function fromUser(User $user): self
    {
        $self = new self();
        $self->name = $user->name;
        $self->preferredPlaceId = $user->preferredPlaceId;
        $self->phone = $user->phone;
        $self->deliveryStreet = $user->deliveryStreet;
        $self->deliveryCity = $user->deliveryCity;
        $self->deliveryZip = $user->deliveryZip;
        $self->companyInvoicing = $user->companyInvoicing;
        $self->companyName = $user->companyName;
        $self->companyId = $user->companyId;
        $self->companyVatId = $user->companyVatId;
        $self->invoicingStreet = $user->invoicingStreet;
        $self->invoicingCity = $user->invoicingCity;
        $self->invoicingZip = $user->invoicingZip;

        return $self;
    }
}
