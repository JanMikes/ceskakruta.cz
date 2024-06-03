<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use CeskaKruta\Web\Value\User;

final class UserInfoFormData
{
    public null|string $name = null;
    public null|int $preferredPlaceId = null;
    public null|string $phone = null;
    public null|string $deliveryStreet = null;
    public null|string $deliveryCity = null;
    public null|string $deliveryZip = null;
    public bool $companyInvoicing = false;
    public null|string $companyName = null;
    public null|string $companyId = null;
    public null|string $invoicingStreet = null;
    public null|string $invoicingCity = null;
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
        $self->invoicingStreet = $user->invoicingStreet;
        $self->invoicingCity = $user->invoicingCity;
        $self->invoicingZip = $user->invoicingZip;

        return $self;
    }
}
