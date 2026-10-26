<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\CalendarColdFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Option `product_types`: `Query\Admin\GetProductTypes::choices()`.
 *
 * @extends AbstractType<CalendarColdFormData>
 */
final class CalendarColdFormType extends AbstractType
{
    use CalendarWeekFormFields;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('product_tp_id', ChoiceType::class, [
            'label' => 'Produkt',
            'property_path' => 'productTpId',
            'choices' => $options['product_types'],
            'placeholder' => '-- Vyberte --',
        ]);

        self::addWeekFields($builder);

        $builder
            ->add('weight_from', NumberType::class, [
                'label' => 'Váha od (kg)',
                'property_path' => 'weightFrom',
                'invalid_message' => 'Váha musí být číslo!',
                'attr' => ['inputmode' => 'decimal'],
            ])
            ->add('weight_to', NumberType::class, [
                'label' => 'Váha do (kg)',
                'property_path' => 'weightTo',
                'invalid_message' => 'Váha musí být číslo!',
                'attr' => ['inputmode' => 'decimal'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarColdFormData::class,
        ]);
        $resolver->setRequired('product_types');
        $resolver->setAllowedTypes('product_types', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'calendar_cold';
    }
}
