<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageUserFormFactory` + `is_admin` (PLAN §6 Q7), the password is never part of it.
 */
final class UserFormData
{
    #[Assert\NotBlank(message: 'Prosím vyplňte e-mail!')]
    #[Assert\Email(message: 'Prosím vyplňte správný formát emailu!')]
    #[Assert\Length(max: 100)]
    public null|string $email = null;

    #[Assert\Length(max: 100)]
    public null|string $name = null;

    #[Assert\Length(max: 255)]
    public null|string $phone = null;

    public null|int $preferredPlaceId = null;

    #[Assert\Length(max: 255)]
    public null|string $deliveryStreet = null;

    #[Assert\Length(max: 255)]
    public null|string $deliveryCity = null;

    #[Assert\Length(max: 255)]
    public null|string $deliveryZip = null;

    public bool $companyInvoicing = false;

    #[Assert\Length(max: 255)]
    public null|string $companyName = null;

    #[Assert\Length(max: 255)]
    public null|string $companyId = null;

    #[Assert\Length(max: 255)]
    public null|string $companyVatId = null;

    #[Assert\Length(max: 255)]
    public null|string $invoicingStreet = null;

    #[Assert\Length(max: 255)]
    public null|string $invoicingCity = null;

    #[Assert\Length(max: 255)]
    public null|string $invoicingZip = null;

    public bool $allowWholesale = false;

    public bool $isCeskakruta = false;

    public bool $isAdmin = false;

    public static function fromUser(AdminUser $user): self
    {
        $formData = new self();
        $formData->email = $user->email;
        $formData->name = $user->name;
        $formData->phone = $user->phone;
        $formData->preferredPlaceId = $user->preferredPlaceId;
        $formData->deliveryStreet = $user->deliveryStreet;
        $formData->deliveryCity = $user->deliveryCity;
        $formData->deliveryZip = $user->deliveryZip;
        $formData->companyInvoicing = $user->companyInvoicing;
        $formData->companyName = $user->companyName;
        $formData->companyId = $user->companyId;
        $formData->companyVatId = $user->companyVatId;
        $formData->invoicingStreet = $user->invoicingStreet;
        $formData->invoicingCity = $user->invoicingCity;
        $formData->invoicingZip = $user->invoicingZip;
        $formData->allowWholesale = $user->allowWholesale;
        $formData->isCeskakruta = $user->isCeskakruta;
        $formData->isAdmin = $user->isAdmin;

        return $formData;
    }
}
