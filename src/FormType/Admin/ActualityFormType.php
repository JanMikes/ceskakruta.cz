<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\ActualityFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ActualityFormData>
 */
final class ActualityFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Datum',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'invalid_message' => 'Zadejte platné datum.',
            ])
            ->add('name', TextType::class, ['label' => 'Nadpis', 'attr' => ['maxlength' => 50]])
            ->add('text', WysiwygType::class, ['label' => 'Text email'])
            ->add('text_full', WysiwygType::class, ['label' => 'Text web', 'property_path' => 'textFull']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActualityFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'actuality';
    }
}
