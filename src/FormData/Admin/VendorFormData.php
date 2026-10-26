<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminVendor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageVendorFormFactory`: everything optional, max 50 characters (zip 5), e-mail validated when filled.
 */
final class VendorFormData
{
    #[Assert\Length(max: 50)]
    public null|string $place = null;

    #[Assert\Length(max: 50)]
    public null|string $name = null;

    #[Assert\Length(max: 50)]
    public null|string $address = null;

    #[Assert\Length(max: 50)]
    public null|string $city = null;

    #[Assert\Length(max: 5)]
    public null|string $zip = null;

    #[Assert\Length(max: 50)]
    #[Assert\Email]
    public null|string $email = null;

    #[Assert\Length(max: 50)]
    public null|string $web = null;

    #[Assert\Length(max: 50)]
    public null|string $phone = null;

    public static function fromVendor(AdminVendor $vendor): self
    {
        $formData = new self();
        $formData->place = $vendor->place;
        $formData->name = $vendor->name;
        $formData->address = $vendor->address;
        $formData->city = $vendor->city;
        $formData->zip = $vendor->zip;
        $formData->email = $vendor->email;
        $formData->web = $vendor->web;
        $formData->phone = $vendor->phone;

        return $formData;
    }
}
