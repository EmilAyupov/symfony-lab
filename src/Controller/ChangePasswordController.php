<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ChangePasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ChangePasswordController extends AbstractController
{
    #[Route('/api/change-password', name: 'api_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] ?User $user,
        ChangePasswordService $changePasswordService,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if ($user === null) {
            return $this->json([
                'error' => 'Authentication required.',
            ], 401);
        }

        try {
            $data = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            return $this->json([
                'error' => 'Invalid JSON.',
            ], 400);
        }

        foreach (['currentPassword', 'newPassword', 'confirmPassword'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field]) || $data[$field] === '') {
                return $this->json([
                    'error' => sprintf('Field "%s" is required.', $field),
                ], 400);
            }
        }

        try {
            $changePasswordService->changePassword(
                $user,
                $data['currentPassword'],
                $data['newPassword'],
                $data['confirmPassword'],
            );
        } catch (\DomainException $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], 400);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'error' => $exception->getMessage(),
            ], 400);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}
