<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Security;

use CeskaKruta\Web\Exceptions\UserNotRegistered;
use CeskaKruta\Web\Value\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
readonly final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByIdentifier((string) $user->email);
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /**
     * @throws UserNotRegistered
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /**
         * @var false|array{
         *     id: int,
         *     email: string,
         *     password: string,
         * } $data
         */
        $data = $this->connection
            ->executeQuery('SELECT * FROM `user` WHERE active_flag = 1 AND del_flag = 0 AND email = :email', [
                'email' => $identifier,
            ])
            ->fetchAssociative();

        if ($data === false) {
            throw new UserNotRegistered();
        }

        return new User(
            $data['id'],
            $data['email'],
            $data['password'],
            ['ROLE_USER'],
        );
    }
}
