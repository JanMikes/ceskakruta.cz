<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        public int $id,
        public string $email,
        public string $password,
        /** @var array<string> */
        public array $roles,
        public null|string $name,
        public null|int $preferredPlaceId,
        public null|string $phone,
        public null|string $deliveryStreet,
        public null|string $deliveryCity,
        public null|string $deliveryZip,
        public bool $companyInvoicing,
        public null|string $companyName,
        public null|string $companyId,
        public null|string $companyVatId,
        public null|string $invoicingStreet,
        public null|string $invoicingCity,
        public null|string $invoicingZip,
        public bool $wholesaleAllowed,
    ) {
    }


    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function hasFilledDeliveryAddress(): bool
    {
        return $this->name !== null
            && $this->phone !== null
            && $this->deliveryStreet !== null
            && $this->deliveryCity !== null
            && $this->deliveryZip !== null;
    }
}
