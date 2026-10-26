<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\UserFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<UserFormData>
 */
final class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, string> $places */
        $places = $options['places'];

        $builder
            // Text input: browser validation must not hide the server-side error
            ->add('email', TextType::class, ['label' => 'E-mail', 'attr' => ['inputmode' => 'email']])
            ->add('name', TextType::class, ['label' => 'Jméno a příjmení', 'required' => false])
            ->add('phone', TextType::class, ['label' => 'Telefon', 'required' => false])
            ->add('preferred_place_id', ChoiceType::class, [
                'label' => 'Doprava',
                'property_path' => 'preferredPlaceId',
                'required' => false,
                'placeholder' => '-- Vyberte místo --',
                'choices' => array_keys($places),
                'choice_label' => static fn (int $id): string => $places[$id] ?? (string) $id,
            ])
            ->add('delivery_street', TextType::class, self::optional('Ulice pro doručení', 'deliveryStreet'))
            ->add('delivery_city', TextType::class, self::optional('Město pro doručení', 'deliveryCity'))
            ->add('delivery_zip', TextType::class, self::optional('PSČ pro doručení', 'deliveryZip'))
            ->add('company_invoicing', CheckboxType::class, self::optional('Firemní fakturace', 'companyInvoicing'))
            ->add('company_name', TextType::class, self::optional('Název firmy', 'companyName'))
            ->add('company_id', TextType::class, self::optional('IČO', 'companyId'))
            ->add('company_vat_id', TextType::class, self::optional('DIČ', 'companyVatId'))
            ->add('invoicing_street', TextType::class, self::optional('Fakturační ulice', 'invoicingStreet'))
            ->add('invoicing_city', TextType::class, self::optional('Fakturační město', 'invoicingCity'))
            ->add('invoicing_zip', TextType::class, self::optional('Fakturační PSČ', 'invoicingZip'))
            ->add('allow_wholesale', CheckboxType::class, self::optional('Povolit velkoobchod', 'allowWholesale'))
            ->add('is_ceskakruta', CheckboxType::class, self::optional('Uživatel/pobočka České Krůty', 'isCeskakruta'))
            // Own account: a disabled field keeps the stored value whatever is posted (+ guard in the handler)
            ->add('is_admin', CheckboxType::class, self::optional('Administrátor', 'isAdmin') + [
                'disabled' => $options['own_account'],
                'help' => $options['own_account'] ? 'Vlastní administrátorské oprávnění nelze odebrat.' : null,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserFormData::class,
            'own_account' => false,
        ]);
        $resolver->setRequired('places');
        $resolver->setAllowedTypes('places', 'array');
        $resolver->setAllowedTypes('own_account', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'user';
    }

    /**
     * @return array{label: string, property_path: string, required: false}
     */
    private static function optional(string $label, string $propertyPath): array
    {
        return ['label' => $label, 'property_path' => $propertyPath, 'required' => false];
    }
}
