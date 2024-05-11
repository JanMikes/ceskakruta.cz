<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

final class OrderFormData
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $note = '';
    public bool $subscribeToNewsletter = false;
}
