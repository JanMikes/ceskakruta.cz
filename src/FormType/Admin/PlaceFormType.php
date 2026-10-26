<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\PlaceFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Old `ManagePlaceFormFactory`, `price_places` (id => name) are offered only on create.
 *
 * @extends AbstractType<PlaceFormData>
 */
final class PlaceFormType extends AbstractType
{
    private const DAY_ACCUSATIVES = [1 => 'pondělí', 2 => 'úterý', 3 => 'středu', 4 => 'čtvrtek', 5 => 'pátek', 6 => 'sobotu', 7 => 'neděli'];

    private const DAY_NAMES = [1 => 'Pondělí', 2 => 'Úterý', 3 => 'Středa', 4 => 'Čtvrtek', 5 => 'Pátek', 6 => 'Sobota', 7 => 'Neděle'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var null|array<int, string> $pricePlaces */
        $pricePlaces = $options['price_places'];

        if ($pricePlaces !== null) {
            $builder->add('prices_from_place_id', ChoiceType::class, [
                'label' => 'Převzít ceny z prodejny',
                'property_path' => 'pricesFromPlaceId',
                'required' => false,
                'placeholder' => '- Vyberte prodejnu -',
                'choices' => array_keys($pricePlaces),
                'choice_label' => static fn (int $id): string => $pricePlaces[$id] ?? (string) $id,
            ]);
        }

        $builder
            ->add('name', TextType::class, ['label' => 'Název krátký'])
            ->add('title', TextType::class, ['label' => 'Název dlouhý'])
            ->add('allow_only_wholesale', CheckboxType::class, [
                'label' => 'Zobrazovat pouze pro velkoobchod',
                'property_path' => 'allowOnlyWholesale',
                'required' => false,
            ])
            ->add('force_delivery_address', CheckboxType::class, [
                'label' => 'Je rozvoz?',
                'property_path' => 'forceDeliveryAddress',
                'required' => false,
            ])
            ->add('is_cool_balik', CheckboxType::class, [
                'label' => 'Jedná se o rozvoz skrze Cool Balík?',
                'property_path' => 'isCoolBalik',
                'required' => false,
            ])
            ->add('force_packing', CheckboxType::class, [
                'label' => 'Vynutit vakuové balení',
                'property_path' => 'forcePacking',
                'required' => false,
            ]);

        foreach (self::DAY_NAMES as $day => $dayName) {
            $builder
                ->add(sprintf('enable_day_%d', $day), CheckboxType::class, [
                    'label' => 'Prodej na ' . self::DAY_ACCUSATIVES[$day],
                    'property_path' => sprintf('days[%d].enabled', $day),
                    'required' => false,
                ])
                ->add(sprintf('allow_order_day_%d_days_before', $day), TextType::class, [
                    'label' => $dayName . ' potřeba objednat dnů předem',
                    'property_path' => sprintf('days[%d].daysBefore', $day),
                    'required' => false,
                    'attr' => ['inputmode' => 'numeric'],
                ])
                ->add(sprintf('allow_order_day_%d_time', $day), TextType::class, [
                    'label' => 'Čas uzavření objednávek',
                    'property_path' => sprintf('days[%d].time', $day),
                    'required' => false,
                    'attr' => ['placeholder' => 'HH:MM'],
                ]);
        }

        $builder
            ->add('description', WysiwygType::class, ['label' => 'Text do sekce Prodejny'])
            ->add('opening_hours', TextareaType::class, [
                'label' => 'Otevírací doba (web)',
                'property_path' => 'openingHours',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('opening_hours_email', TextareaType::class, [
                'label' => 'Otevírací doba (e-mail)',
                'property_path' => 'openingHoursEmail',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlaceFormData::class,
            'price_places' => null,
        ]);
        $resolver->setAllowedTypes('price_places', ['null', 'array']);
    }

    public function getBlockPrefix(): string
    {
        return 'place';
    }
}
