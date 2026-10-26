<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Exceptions\InvalidPackagesSize;
use CeskaKruta\Web\Value\Admin\AdminProduct;
use CeskaKruta\Web\Value\Admin\PackagesSize;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Old `ManageProductFormFactory` for new products and edit of meat (1) / products (2), plus `packages_size` (PLAN §2.9).
 */
final class ProductFormData
{
    #[Assert\NotBlank(message: 'Název je povinný!')]
    #[Assert\Length(max: 50)]
    public null|string $name = null;

    #[Assert\NotBlank(message: 'Typ je povinný!')]
    #[Assert\Choice(choices: [1, 2])]
    public null|int $type = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['ks', 'kg'])]
    public null|string $unit = 'kg';

    #[Assert\NotNull(message: 'Váha kg musí být číslo, např. 1.5 (celé číslo, nebo desetinné)!')]
    #[Assert\PositiveOrZero]
    public null|float $weightPerUnit = 1.0;

    #[Assert\PositiveOrZero]
    public null|int $price = null;

    #[Assert\Length(max: 100)]
    public null|string $code = null;

    public bool $canBeSliced = false;

    public bool $canBePacked = false;

    public bool $forcePacking = false;

    #[Assert\PositiveOrZero]
    public null|int $pricePack = null;

    public null|string $textAdditional = null;

    public null|string $text = null;

    public null|string $packagesSize = null;

    public static function fromProduct(AdminProduct $product): self
    {
        $formData = new self();
        $formData->name = $product->name;
        $formData->type = $product->type;
        $formData->unit = $product->unit;
        $formData->weightPerUnit = $product->weightPerUnit;
        $formData->price = $product->price;
        $formData->code = $product->code;
        $formData->canBeSliced = $product->canBeSliced;
        $formData->canBePacked = $product->canBePacked;
        $formData->forcePacking = $product->forcePacking;
        $formData->pricePack = $product->pricePack;
        $formData->textAdditional = $product->textAdditional;
        $formData->text = $product->text;
        $formData->packagesSize = $product->packagesSize->toInput();

        return $formData;
    }

    #[Assert\Callback]
    public function validatePackagesSize(ExecutionContextInterface $context): void
    {
        try {
            PackagesSize::fromInput($this->packagesSize ?? '');
        } catch (InvalidPackagesSize $exception) {
            $context->buildViolation($exception->getMessage())
                ->atPath('packagesSize')
                ->addViolation();
        }
    }
}
