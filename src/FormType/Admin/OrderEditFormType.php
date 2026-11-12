<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\OrderEditFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrderEditFormData>
 */
final class OrderEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $optional = static fn (string $label, string $path): array => ['label' => $label, 'property_path' => $path, 'required' => false];

        $builder
            ->add('place_id', ChoiceType::class, [
                'label' => 'Místo',
                'property_path' => 'placeId',
                'choices' => $options['places'],
                'placeholder' => '– vyberte místo –',
            ])
            ->add('date', DateType::class, ['label' => 'Datum vyzvednutí / doručení', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
            ->add('name', TextType::class, ['label' => 'Jméno a příjmení', 'empty_data' => ''])
            ->add('phone', TextType::class, ['label' => 'Telefon', 'empty_data' => ''])
            ->add('email', EmailType::class, ['label' => 'E-mail', 'empty_data' => ''])
            ->add('delivery_street', TextType::class, $optional('Ulice a č. p.', 'deliveryStreet'))
            ->add('delivery_city', TextType::class, $optional('Město', 'deliveryCity'))
            ->add('delivery_postal_code', TextType::class, $optional('PSČ', 'deliveryPostalCode'))
            ->add('pay_by_card', CheckboxType::class, $optional('Platba kartou', 'payByCard'))
            ->add('note', TextareaType::class, $optional('Poznámka', 'note') + ['attr' => ['rows' => 3]])
            ->add('items', CollectionType::class, [
                'label' => false,
                'entry_type' => OrderItemFormType::class,
                'entry_options' => ['label' => false, 'products' => $options['products']],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'error_bubbling' => false,
            ])
            ->add('notify_customer', CheckboxType::class, $optional('Poslat zákazníkovi e-mail se změnami', 'notifyCustomer'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderEditFormData::class,
            'places' => [],
            'products' => [],
        ]);
        $resolver->setAllowedTypes('places', 'array');
        $resolver->setAllowedTypes('products', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'order_edit';
    }
}
