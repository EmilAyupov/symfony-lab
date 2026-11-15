<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuthController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Запрос обрабатывается Symfony Security до вызова этого метода.
        // Если метод будет вызван, значит, механизм JSON-входа не перехватил запрос.
        return new JsonResponse([
            'error' => 'Authentication endpoint was not intercepted.',
        ], 401);
    }
}
