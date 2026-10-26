<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\RecipeFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RecipeFormData>
 */
final class RecipeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název', 'attr' => ['maxlength' => 50]])
            ->add('image', FileType::class, [
                'label' => 'Foto',
                'required' => false,
                'help' => 'JPG, GIF nebo PNG, nejvýše 10 MB.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/gif'],
            ])
            ->add('text', WysiwygType::class, ['label' => 'Text']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecipeFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'recipe';
    }
}
