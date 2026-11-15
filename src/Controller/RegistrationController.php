<?php

namespace App\Controller;

use App\Service\RegistrationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController
{
    public function __construct(
        private RegistrationService $registrationService,
    ) {}

    #[Route("/api/register", name: "api_register", methods: ["POST"])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            return new JsonResponse(["error" => "Invalid JSON"], 400);
        }

        if (
            !isset($data["email"], $data["password"]) ||
            !is_string($data["email"]) ||
            !is_string($data["password"])
        ) {
            return new JsonResponse(
                [
                    "error" => "Email and password are required.",
                ],
                400,
            );
        }

        try {
            $user = $this->registrationService->register(
                $data["email"],
                $data["password"],
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(
                [
                    "error" => $exception->getMessage(),
                ],
                400,
            );
        } catch (\DomainException $exception) {
            return new JsonResponse(
                [
                    "error" => $exception->getMessage(),
                ],
                409,
            );
        }

        return new JsonResponse(
            [
                "id" => $user->getId(),
                "email" => $user->getEmail(),
                "roles" => $user->getRoles(),
            ],
            201,
        );
    }
}
