<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Message\EditUserInfo;
use CeskaKruta\Web\Message\RegisterUser;
use CeskaKruta\Web\Services\Security\PasswordHasher;
use Doctrine\DBAL\Connection;

readonly final class UserService
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private Connection $connection,
    ) {
    }

    public function register(RegisterUser $message): void
    {
        $password = $this->passwordHasher->hash($message->plainTextPassword);
        $now = new \DateTimeImmutable();

        $this->connection->insert('`user`', [
            'name' => $message->name,
            'email' => $message->email,
            'phone' => $message->phone,
            'password' => $password,
            'is_admin' => 0,
            'active_flag' => 1,
            'ins_dt' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(EditUserInfo $message): void
    {
        $this->connection->update(
            '`user`',
            [
                'name' => $message->name,
                'phone' => $message->phone,
                'preferred_place_id' => $message->preferredPlaceId,
                'company_name' => $message->companyName,
                'company_id' => $message->companyId,
                'delivery_street' => $message->deliveryStreet,
                'delivery_city' => $message->deliveryCity,
                'delivery_zip' => $message->deliveryZip,
                'company_invoicing' => $message->companyInvoicing,
                'invoicing_street' => $message->invoicingStreet,
                'invoicing_city' => $message->invoicingCity,
                'invoicing_zip' => $message->invoicingZip,
            ],
            [
                'email' => $message->email,
            ],
        );
    }

    public function changePassword(string $email, string $newPlainTextPassword): void
    {
        $password = $this->passwordHasher->hash($newPlainTextPassword);

        $this->connection->update(
            '`user`',
            [
                'password' => $password,
            ],
            [
                'email' => $email,
            ],
        );
    }
}
