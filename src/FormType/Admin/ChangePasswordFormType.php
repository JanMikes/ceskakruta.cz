<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\ChangePasswordFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ChangePasswordFormData>
 */
final class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Field names = contract of docs/admin/PLAN.md §4.6
            ->add('old_password', PasswordType::class, [
                'property_path' => 'oldPassword',
                'label' => 'Staré heslo',
            ])
            ->add('new_password', RepeatedType::class, [
                'property_path' => 'newPassword',
                'type' => PasswordType::class,
                'invalid_message' => 'Hesla se neshodují.',
                'first_options' => ['label' => 'Nové heslo'],
                'second_options' => ['label' => 'Nové heslo znovu'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'change_password';
    }
}
