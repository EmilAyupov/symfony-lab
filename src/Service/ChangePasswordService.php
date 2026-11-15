<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Exception\UnsupportedPasswordTypeException;

class ChangePasswordService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function changePassword(
        User $user,
        string $currentPassword,
        string $newPassword,
        string $confirmPassword,
    ): void {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new \DomainException('Current password is incorrect.');
        }

        if (mb_strlen($newPassword) < 8) {
            throw new \InvalidArgumentException(
                'New password must contain at least 8 characters.'
            );
        }

        if ($newPassword !== $confirmPassword) {
            throw new \InvalidArgumentException(
                'Password confirmation does not match.'
            );
        }

        if ($currentPassword === $newPassword) {
            throw new \InvalidArgumentException(
                'New password must differ from the current password.'
            );
        }

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $newPassword)
        );
    }
}
