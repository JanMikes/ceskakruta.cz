<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormType;

use CeskaKruta\Web\FormData\SubscribeNewsletterFormData;
use Symfony\Component\DependencyInjection\Compiler\CheckAliasValidityPass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SubscribeNewsletterFormType>
 */
final class SubscribeNewsletterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class);

        $builder->add('agreement', CheckboxType::class, [
            'required' => true,
            'label' => false,
            'mapped' => false,
        ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubscribeNewsletterFormData::class,
        ]);
    }
}
