<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use Generator;
use CeskaKruta\Web\Entity\ProductVariant;
use CeskaKruta\Web\FormData\AddToCartFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AddToCartFormType>
 */
final class AddToCartFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<ProductVariant> $variants */
        $variants = $options['variants'];

        $builder->add('variantId', ChoiceType::class, [
            'choices' => $this->mapVariantsToChoices($variants),
            'placeholder' => '- Vyberte variantu -',
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Do košíku'
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddToCartFormData::class,
            'variants' => [],
        ]);
    }

    /**
     * @param list<ProductVariant> $variants
     *
     * @return Generator<string, string>
     */
    private function mapVariantsToChoices(array $variants): Generator
    {
        foreach ($variants as $variant) {
            yield "$variant->name ({$variant->price->valueWithoutVat} Kč)" => $variant->id->toString();
        }
    }
}
