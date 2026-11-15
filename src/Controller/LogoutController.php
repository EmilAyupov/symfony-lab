<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController
{
    #[Route("/api/logout", name: "api_logout", methods: ["POST"])]
    public function logout(): JsonResponse
    {
        return new JsonResponse([
            "message" => "Logged out successfully.",
        ]);
    }
}
