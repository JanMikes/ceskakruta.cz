<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use CeskaKruta\Web\FormData\Admin\CalendarDeadFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CalendarDeadFormData>
 */
final class CalendarDeadFormType extends AbstractType
{
    use CalendarWeekFormFields;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('farma_doubrava', CheckboxType::class, ['label' => 'Farma Doubrava', 'required' => false, 'property_path' => 'farmaDoubrava'])
            ->add('farma_rychvald', CheckboxType::class, ['label' => 'Farma Rychvald', 'required' => false, 'property_path' => 'farmaRychvald']);

        self::addWeekFields($builder);

        $builder
            ->add('week_age', TextType::class, [
                'label' => 'Stáří v týdnech',
                'required' => false,
                'property_path' => 'weekAge',
                'attr' => ['inputmode' => 'numeric', 'maxlength' => 2],
            ])
            ->add('weight', TextType::class, ['label' => 'Přibližná váha', 'required' => false])
            ->add('price_mix', TextType::class, ['label' => 'Cena mix', 'required' => false, 'property_path' => 'priceMix'])
            ->add('price_man', TextType::class, ['label' => 'Cena krocan', 'required' => false, 'property_path' => 'priceMan'])
            ->add('price_woman', TextType::class, ['label' => 'Cena krůta', 'required' => false, 'property_path' => 'priceWoman'])
            ->add('note', TextType::class, ['label' => 'Poznámka', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarDeadFormData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'calendar_dead';
    }
}
