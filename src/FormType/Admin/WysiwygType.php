<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormType\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Rich text field: a plain textarea enhanced by the Tiptap Stimulus controller `wysiwyg` (docs/admin/PLAN.md §3.12).
 * The submitted value is the HTML of the textarea, so tests and no-JS work like a textarea.
 *
 * @extends AbstractType<null|string>
 */
final class WysiwygType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'attr' => [
                'data-controller' => 'wysiwyg',
                'rows' => 12,
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'admin_wysiwyg';
    }
}
