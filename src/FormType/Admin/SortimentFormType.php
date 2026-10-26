<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\SortimentFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SortimentFormData>
 */
final class SortimentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Název', 'attr' => ['maxlength' => 50]])
            ->add('text', WysiwygType::class, ['label' => 'Text']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SortimentFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sortiment';
    }
}
