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

    public function refreshUser(UserInterface $user): User
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
    public function loadUserByIdentifier(string $identifier): User
    {
        /**
         * @var false|array{
         *     id: int,
         *     email: string,
         *     password: string,
         *     name: null|string,
         *     preferred_place_id: null|int,
         *     phone: null|string,
         *     delivery_street: null|string,
         *     delivery_city: null|string,
         *     delivery_zip: null|string,
         *     company_name: null|string,
         *     company_id: null|string,
         *     company_vat_id: null|string,
         *     company_invoicing: int,
         *     invoicing_street: null|string,
         *     invoicing_city: null|string,
         *     invoicing_zip: null|string,
         *     is_admin: bool,
         *     allow_wholesale: bool,
         *     is_ceskakruta: bool,
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

        $roles = ['ROLE_USER'];

        if ($data['is_admin']) {
            $roles[] = 'ROLE_ADMIN';
        }

        return new User(
            id: $data['id'],
            email: $data['email'],
            password: $data['password'],
            roles: $roles,
            name: $data['name'],
            preferredPlaceId: $data['preferred_place_id'],
            phone: $data['phone'],
            deliveryStreet: $data['delivery_street'],
            deliveryCity: $data['delivery_city'],
            deliveryZip: $data['delivery_zip'],
            companyInvoicing: $data['company_invoicing'] === 1,
            companyName: $data['company_name'],
            companyId: $data['company_id'],
            companyVatId: $data['company_vat_id'],
            invoicingStreet: $data['invoicing_street'],
            invoicingCity: $data['invoicing_city'],
            invoicingZip: $data['invoicing_zip'],
            wholesaleAllowed: (bool) $data['allow_wholesale'],
            isCeskakruta: (bool) $data['is_ceskakruta'],
        );
    }
}
