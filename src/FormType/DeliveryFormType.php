<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\DeliveryFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<DeliveryFormData>
 */
final class DeliveryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('street', TextType::class, [
            'label' => 'Ulice',
            'required' => true,
            'empty_data' => '',
        ]);

        $builder->add('city', TextType::class, [
            'label' => 'Město',
            'required' => true,
            'empty_data' => '',
        ]);

        $builder->add('postalCode', TextType::class, [
            'label' => 'PSČ',
            'required' => true,
            'empty_data' => '',
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryFormData::class,
        ]);
    }
}
