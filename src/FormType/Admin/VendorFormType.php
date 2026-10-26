<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\VendorFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<VendorFormData>
 */
final class VendorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('place', TextType::class, ['label' => 'Místo', 'required' => false])
            ->add('name', TextType::class, ['label' => 'Název prodejce', 'required' => false])
            ->add('address', TextType::class, ['label' => 'Adresa', 'required' => false])
            ->add('city', TextType::class, ['label' => 'Město', 'required' => false])
            ->add('zip', TextType::class, ['label' => 'PSČ', 'required' => false])
            // Text input: browser validation must not hide the server-side error
            ->add('email', TextType::class, ['label' => 'E-mail', 'required' => false, 'attr' => ['inputmode' => 'email']])
            ->add('web', TextType::class, ['label' => 'Web', 'required' => false])
            ->add('phone', TextType::class, ['label' => 'Telefon', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VendorFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'vendor';
    }
}
