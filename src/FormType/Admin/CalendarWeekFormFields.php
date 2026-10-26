<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Fields of `FormData\Admin\CalendarWeekFields`; text inputs, so "1.5" is rejected instead of rounded by IntegerType.
 */
trait CalendarWeekFormFields
{
    /**
     * @template TData
     * @param FormBuilderInterface<TData> $builder
     */
    private static function addWeekFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('week_number', TextType::class, [
                'label' => 'Číslo týdne',
                'property_path' => 'weekNumber',
                'attr' => ['inputmode' => 'numeric', 'maxlength' => 2],
            ])
            ->add('year', TextType::class, [
                'label' => 'Rok',
                'attr' => ['inputmode' => 'numeric', 'maxlength' => 4],
            ]);
    }
}
