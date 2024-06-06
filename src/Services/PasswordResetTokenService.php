<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Exceptions\InvalidResetPasswordToken;
use Doctrine\DBAL\Connection;

readonly final class PasswordResetTokenService
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function create(int $userId): string
    {
        $bytes = random_bytes(20);
        $token = substr(bin2hex($bytes), 0, 20);

        $now = new \DateTimeImmutable();
        $validUntil = new \DateTimeImmutable('+12 hours');

        $this->connection->insert('password_reset_token', [
            'user_id' => $userId,
            'token' => $token,
            'valid_until' => $validUntil->format('Y-m-d H:i:s'),
            'ins_dt' => $now->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * @throws InvalidResetPasswordToken
     */
    public function getTokenUserId(string $token): int
    {
        $now = new \DateTimeImmutable();

        /** @var array<array{user_id: int}> $tokens */
        $tokens = $this->connection
            ->executeQuery('SELECT * FROM password_reset_token WHERE active_flag = 1 AND del_flag = 0 AND token = :token AND valid_until >= :now', [
                'token' => $token,
                'now' => $now->format('Y-m-d H:i:s'),
            ])
            ->fetchAllAssociative();

        if (count($tokens) > 0) {
            return $tokens[0]['user_id'];
        }

        throw new InvalidResetPasswordToken();
    }

    public function useToken(string $token): void
    {
        $this->connection->update(
            'password_reset_token',
            [
                'active_flag' => 0,
            ],
            [
                'token' => $token,
            ],
        );
    }
}
