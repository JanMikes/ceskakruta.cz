<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\OrderItemFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrderItemFormData>
 */
final class OrderItemFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $number = static fn (string $label, string $path, string $step = 'any'): array => [
            'label' => $label,
            'property_path' => $path,
            'required' => false,
            'html5' => true,
            'attr' => ['step' => $step, 'min' => 0],
        ];

        $builder
            ->add('id', HiddenType::class)
            ->add('product_id', ChoiceType::class, [
                'label' => 'Produkt',
                'property_path' => 'productId',
                'choices' => $options['products'],
                'placeholder' => '– vyberte produkt –',
                'attr' => ['data-admin--order-items-target' => 'product', 'data-action' => 'admin--order-items#productChanged'],
            ])
            ->add('amount', NumberType::class, ['required' => true] + $number('Množství', 'amount'))
            ->add('weight_from', NumberType::class, $number('Váha od (kg)', 'weightFrom'))
            ->add('weight_to', NumberType::class, $number('Váha do (kg)', 'weightTo'))
            ->add('is_sliced', CheckboxType::class, ['label' => 'Nakrájet', 'property_path' => 'isSliced', 'required' => false])
            ->add('is_packed', CheckboxType::class, ['label' => 'Vakuovat', 'property_path' => 'isPacked', 'required' => false])
            ->add('price_per_unit', IntegerType::class, [
                'label' => 'Cena za jednotku (Kč)',
                'property_path' => 'pricePerUnit',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => 'cena z webu'],
            ])
            ->add('note', TextType::class, ['label' => 'Poznámka k položce', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItemFormData::class,
            'products' => [],
        ]);
        $resolver->setAllowedTypes('products', 'array');
    }
}
