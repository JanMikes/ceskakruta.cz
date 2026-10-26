<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminPlace;
use Symfony\Component\Validator\Constraints as Assert;

final class PlaceFormData
{
    /** Create only: copy the active prices (`product_place`) of this place */
    public null|int $pricesFromPlaceId = null;

    #[Assert\NotBlank(message: 'Název je povinný!')]
    #[Assert\Length(max: 255)]
    public null|string $name = null;

    #[Assert\NotBlank(message: 'Název je povinný!')]
    #[Assert\Length(max: 255)]
    public null|string $title = null;

    public bool $allowOnlyWholesale = false;

    public bool $forceDeliveryAddress = false;

    public bool $isCoolBalik = false;

    public bool $forcePacking = false;

    /**
     * @var array<int<1, 7>, PlaceDayFormData>
     */
    #[Assert\Valid]
    public array $days;

    public null|string $description = null;

    public null|string $openingHours = null;

    public null|string $openingHoursEmail = null;

    public function __construct()
    {
        $this->days = [];
        for ($day = 1; $day <= 7; $day++) {
            $this->days[$day] = new PlaceDayFormData();
        }
    }

    public static function fromPlace(AdminPlace $place): self
    {
        $formData = new self();
        $formData->name = $place->name;
        $formData->title = $place->title;
        $formData->allowOnlyWholesale = $place->allowOnlyWholesale;
        $formData->forceDeliveryAddress = $place->forceDeliveryAddress;
        $formData->isCoolBalik = $place->isCoolBalik;
        $formData->forcePacking = $place->forcePacking;
        $formData->description = $place->description;
        $formData->openingHours = $place->openingHours;
        $formData->openingHoursEmail = $place->openingHoursEmail;

        foreach ($place->days as $day => $placeDay) {
            $formData->days[$day] = PlaceDayFormData::fromDay($placeDay);
        }

        return $formData;
    }
}
