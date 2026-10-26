<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Rules of the old `User:changePassword` form (docs/admin/PLAN.md §2.2).
 */
final class ChangePasswordFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 4)]
    #[SecurityAssert\UserPassword(message: 'Bylo zadáno chybné staré heslo!')]
    public string $oldPassword = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 4, max: 20)]
    public string $newPassword = '';
}
