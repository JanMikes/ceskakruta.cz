<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminContent;

/**
 * Old `ManageContentFormFactory` on edit: only the text, `code` (read by the frontend) and `name` are fixed.
 */
final class ContentFormData
{
    public null|string $text = null;

    public static function fromContent(AdminContent $content): self
    {
        $formData = new self();
        $formData->text = $content->text;

        return $formData;
    }
}
