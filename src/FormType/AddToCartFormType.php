<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\AddToCartFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AddToCartFormType>
 */
final class AddToCartFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('week', HiddenType::class);
        $builder->add('year', HiddenType::class);

        $builder->add('productId', HiddenType::class);

        $builder->add('quantity', NumberType::class);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddToCartFormData::class,
            'variants' => [],
        ]);
    }
}
