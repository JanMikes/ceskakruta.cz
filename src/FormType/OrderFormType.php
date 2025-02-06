<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\OrderFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrderFormData>
 */
final class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Jméno a příjmení',
            'required' => true,
            'empty_data' => '',
        ]);

        $builder->add('email', EmailType::class, [
            'label' => 'E-mail',
            'required' => true,
            'empty_data' => '',
        ]);

        $builder->add('phone', TextType::class, [
            'label' => 'Telefon',
            'required' => true,
            'empty_data' => '',
        ]);

        $builder->add('note', TextareaType::class, [
            'label' => 'Poznámka',
            'required' => false,
        ]);

        $builder->add('payByCard', ChoiceType::class, [
            'label' => 'Způsob platby při předávce',
            'choices' => [
                'Platba hotově' => '',
                'Platba kartou' => true,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => false,
        ]);

        $builder->add('termsAgreement', CheckboxType::class, [
            'required' => true,
            'label' => false,
            'mapped' => false,
        ]);

        $builder->add('subscribeToNewsletter', CheckboxType::class, [
            'required' => false,
            'label' => 'Chci zasílat informace a novinky z České Krůty',
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderFormData::class,
        ]);
    }
}
