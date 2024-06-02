<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\RegistrationFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RegistrationFormData>
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Jméno a příjmení',
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'E-mail',
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'Telefon',
            ])
            ->add('password', PasswordType::class, [
                'required' => true,
                'label' => 'Heslo',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationFormData::class,
        ]);
    }
}
