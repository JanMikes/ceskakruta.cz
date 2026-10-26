<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\TurkeyProductFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TurkeyProductFormData>
 */
final class TurkeyProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price', IntegerType::class, [
                'label' => 'Cena (v Kč)',
                'required' => false,
                'attr' => ['min' => 0],
                'invalid_message' => 'Cena musí být celé číslo!',
            ])
            ->add('code', TextType::class, ['label' => 'Kód produktu (do Pohody)', 'required' => false])
            ->add('text', WysiwygType::class, ['label' => 'Popis']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TurkeyProductFormData::class,
            // Fields of the full product form posted anyway are ignored, never written (PLAN §6, Phase 1 review item 10)
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'product';
    }
}
