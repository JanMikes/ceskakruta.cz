<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\PlaceClosedDayFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PlaceClosedDayFormData>
 */
final class PlaceClosedDayFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, string> $places */
        $places = $options['places'];

        $builder
            ->add('date', DateType::class, [
                'label' => 'Datum',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('place_id', ChoiceType::class, [
                'label' => 'Prodejna',
                'property_path' => 'placeId',
                'required' => false,
                'placeholder' => '- Všechny prodejny -',
                'choices' => array_keys($places),
                'choice_label' => static fn (int $id): string => $places[$id] ?? (string) $id,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlaceClosedDayFormData::class,
        ]);
        $resolver->setRequired('places');
        $resolver->setAllowedTypes('places', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'place_closed_day';
    }
}
