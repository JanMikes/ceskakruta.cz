<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Content;

use CeskaKruta\Web\FormData\Admin\ContentFormData;

readonly final class EditContent
{
    public function __construct(
        public int $id,
        public int $adminId,
        public null|string $text,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, ContentFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            text: $formData->text,
        );
    }
}
