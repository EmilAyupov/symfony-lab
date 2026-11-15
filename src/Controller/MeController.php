<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json([
                'error' => 'Authentication required.',
            ], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
