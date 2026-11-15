<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(string $email, string $plainPassword): User
    {
        $email = mb_strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        if (mb_strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException('Password must contain at least 8 characters.');
        }

        if ($this->userRepository->findOneBy(['email' => $email]) !== null) {
            throw new \DomainException('An account with this email already exists.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $plainPassword)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
