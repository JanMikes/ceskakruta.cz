<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\ProductFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ProductFormData>
 */
final class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název', 'attr' => ['maxlength' => 50]])
            ->add('type', ChoiceType::class, [
                'label' => 'Typ produktu',
                'choices' => ['Maso' => 1, 'Výrobky' => 2],
                'placeholder' => '- Vyberte typ -',
                'invalid_message' => 'Vyberte typ produktu.',
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Prodejní jednotka',
                'choices' => ['ks' => 'ks', 'kg' => 'kg'],
                'invalid_message' => 'Vyberte prodejní jednotku.',
            ])
            ->add('weight_per_unit', NumberType::class, [
                'label' => 'Váha vč. balení v KG',
                'property_path' => 'weightPerUnit',
                'help' => 'U produktů na váhu nechat 1',
                'html5' => true,
                'attr' => ['step' => 'any', 'min' => 0],
                'invalid_message' => 'Váha kg musí být číslo, např. 1.5 (celé číslo, nebo desetinné)!',
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Cena (v Kč)',
                'required' => false,
                'attr' => ['min' => 0],
                'invalid_message' => 'Cena musí být celé číslo!',
            ])
            ->add('code', TextType::class, ['label' => 'Kód produktu (do Pohody)', 'required' => false])
            ->add('can_be_sliced_flag', CheckboxType::class, [
                'label' => 'Možnost krájení',
                'property_path' => 'canBeSliced',
                'required' => false,
            ])
            ->add('can_be_packed_flag', CheckboxType::class, [
                'label' => 'Možnost vakuového balení',
                'property_path' => 'canBePacked',
                'required' => false,
            ])
            ->add('force_packing', CheckboxType::class, [
                'label' => 'Vynutit vakuové balení',
                'property_path' => 'forcePacking',
                'required' => false,
            ])
            ->add('price_pack', IntegerType::class, [
                'label' => 'Cena balení (v Kč)',
                'property_path' => 'pricePack',
                'required' => false,
                'attr' => ['min' => 0],
                'invalid_message' => 'Cena musí být celé číslo!',
            ])
            ->add('packages_size', TextType::class, [
                'label' => 'Velikosti balení (g)',
                'property_path' => 'packagesSize',
                'required' => false,
                'help' => 'Gramy oddělené čárkou, např. 500, 1000, 1500. Prázdné = bez výběru velikosti balení.',
            ])
            ->add('text_additional', WysiwygType::class, ['label' => 'Text pod čarou', 'property_path' => 'textAdditional'])
            ->add('text', WysiwygType::class, ['label' => 'Popis']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'product';
    }
}
