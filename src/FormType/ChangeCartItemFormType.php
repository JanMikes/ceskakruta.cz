<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\ChangeCartItemFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ChangeCartItemFormData>
 */
final class ChangeCartItemFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cartKey', HiddenType::class);
        $builder->add('quantity', NumberType::class);
        $builder->add('note', TextType::class, [
            'required' => false,
            'label' => 'Poznámka',
        ]);
        $builder->add('slice', HiddenType::class);
        $builder->add('pack', HiddenType::class);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangeCartItemFormData::class,
        ]);
    }
}
