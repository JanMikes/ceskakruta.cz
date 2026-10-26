<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\BoxxiFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<BoxxiFormData>
 */
final class BoxxiFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weight', NumberType::class, ['label' => 'Finální váha (kg)', 'html5' => true, 'attr' => ['step' => 'any', 'min' => 0]])
            ->add('cool_balik_price', NumberType::class, ['label' => 'Finální cena', 'property_path' => 'coolBalikPrice', 'html5' => true, 'attr' => ['step' => 'any', 'min' => 0]])
            ->add('cool_balik_box_count', IntegerType::class, ['label' => 'Počet boxů', 'property_path' => 'coolBalikBoxCount', 'attr' => ['min' => 0]])
            ->add('date', DateType::class, ['label' => 'Datum doručení', 'widget' => 'single_text', 'input' => 'datetime_immutable']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BoxxiFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'boxxi';
    }
}
