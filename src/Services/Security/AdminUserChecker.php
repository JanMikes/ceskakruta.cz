<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The old admin let any logged-in user in, the new one only admins (docs/admin/PLAN.md §1.4).
 * Checked after the credentials, so the message does not reveal which e-mails are registered.
 */
readonly final class AdminUserChecker implements UserCheckerInterface
{
    public const NOT_ADMIN_MESSAGE = 'Do administrace mají přístup pouze administrátoři.';

    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw new CustomUserMessageAccountStatusException(self::NOT_ADMIN_MESSAGE);
        }
    }
}
