<?php

namespace App\Controller;

use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryService $categoryService,
    )
    {
    }

    #[Route('/categories', name: 'app_category', methods: ['GET'])]
    public function index(): Response
    {
        return new JsonResponse(
            $this->categoryService->getAll()
        );
    }

    #[Route('/categories/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $category = $this->categoryService->getById($id);

        if ($category === null) {
            return new JsonResponse(
                ['error' => 'Category not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($category);

    }

    #[Route('/categories', name: 'app_category_create', methods: ['POST'])]
    public function store(\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '') {
            return new JsonResponse(
                ['error' => 'Field "name" is required and must be a non-empty string'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (isset($data['description']) && !is_string($data['description']) && $data['description'] !== null) {
            return new JsonResponse(
                ['error' => 'Field "description" must be a string or null'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $category = $this->categoryService->create(
            trim($data['name']),
            $data['description'] ?? null
        );

        return new JsonResponse($category, Response::HTTP_CREATED);
    }

    #[Route('/categories/{id}', name: 'app_category_update', methods: ['PUT'])]
    public function update(
        int                                       $id,
        \Symfony\Component\HttpFoundation\Request $request
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !is_array($data)
            || !isset($data['name'])
            || !is_string($data['name'])
            || trim($data['name']) === ''
        ) {
            return new JsonResponse(
                ['error' => 'Field "name" is required and must be a non-empty string'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (
            isset($data['description'])
            && !is_string($data['description'])
            && $data['description'] !== null
        ) {
            return new JsonResponse(
                ['error' => 'Field "description" must be a string or null'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $category = $this->categoryService->update(
            $id,
            trim($data['name']),
            $data['description'] ?? null
        );

        if ($category === null) {
            return new JsonResponse(
                ['error' => 'Category not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($category, Response::HTTP_OK);
    }

    #[Route('/categories/{id}', name: 'app_category_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $result = $this->categoryService->delete($id);

        if ($result === 'not_found') {
            return new JsonResponse(
                ['error' => 'Category not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($result === 'has_products') {
            return new JsonResponse(
                ['error' => 'Cannot delete category because it has products'],
                Response::HTTP_CONFLICT
            );
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
