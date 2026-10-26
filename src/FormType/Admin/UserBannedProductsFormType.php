<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\UserBannedProductsFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<UserBannedProductsFormData>
 */
final class UserBannedProductsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, string> $products */
        $products = $options['products'];

        $builder->add('products', ChoiceType::class, [
            'label' => 'Zakázané produkty',
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'choices' => array_keys($products),
            'choice_label' => static fn (int $id): string => $products[$id] ?? (string) $id,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserBannedProductsFormData::class,
        ]);
        $resolver->setRequired('products');
        $resolver->setAllowedTypes('products', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'banned_products';
    }
}
