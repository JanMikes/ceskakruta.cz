<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\UserInfoFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<UserInfoFormData>
 */
final class UserInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Jméno a příjmení',
            'required' => false,
        ]);

        $builder->add('phone', TextType::class, [
            'label' => 'Telefon',
            'required' => false,
        ]);

        $builder->add('deliveryStreet', TextType::class, [
            'label' => 'Doručovací ulice',
            'required' => false,
        ]);

        $builder->add('deliveryCity', TextType::class, [
            'label' => 'Doručovací město',
            'required' => false,
        ]);

        $builder->add('deliveryZip', TextType::class, [
            'label' => 'Doručovací PSČ',
            'required' => false,
        ]);

        $builder->add('companyInvoicing', CheckboxType::class, [
            'label' => 'Fakturovat na společnost',
            'required' => false,
        ]);

        $builder->add('companyName', TextType::class, [
            'label' => 'Název společnosti',
            'required' => false,
        ]);

        $builder->add('companyId', TextType::class, [
            'label' => 'IČ společnosti',
            'required' => false,
        ]);

        $builder->add('invoicingStreet', TextType::class, [
            'label' => 'Fakturační ulice',
            'required' => false,
        ]);

        $builder->add('invoicingCity', TextType::class, [
            'label' => 'Fakturační město',
            'required' => false,
        ]);

        $builder->add('invoicingZip', TextType::class, [
            'label' => 'Fakturační PSČ',
            'required' => false,
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserInfoFormData::class,
        ]);
    }
}
