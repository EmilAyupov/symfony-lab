<?php

namespace App\Controller;

use App\DTO\CreateProductRequest;
use App\Exception\ProductNotFoundException;
use App\Service\ProductService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController
{
    public function __construct(
        private ProductService      $productService,
        private SerializerInterface $serializer,
    )
    {
    }


    #[Route('/products', name: 'app_products', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('categoryId');

        if ($categoryId !== null) {
            if (!ctype_digit($categoryId) || (int) $categoryId < 1) {
                return new JsonResponse(
                    ['error' => 'categoryId must be a positive integer'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $products = $this->productService->getByCategoryId((int) $categoryId);
        } else {
            $products = $this->productService->getAll();
        }

        $json = $this->serializer->serialize(
            $products,
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/products', name: 'app_products_add', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            return new JsonResponse(
                ['error' => 'Invalid JSON'],
                400
            );
        }

        $dto = new CreateProductRequest(
            name: $data['name'] ?? null,
            price: $data['price'] ?? null,
            categoryId: $data['categoryId'] ?? null,
        );

        $product = $this->productService->create($dto);

        $json = $this->serializer->serialize(
            $product,
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/products/{id}', name: 'app_products_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException) {
            return new JsonResponse(
                ['error' => 'Invalid JSON'],
                400
            );
        }

        $dto = new CreateProductRequest(
            name: $data['name'] ?? null,
            price: $data['price'] ?? null,
            categoryId: $data['categoryId'] ?? null,
        );

        $product = $this->productService->update($id, $dto);

        $json = $this->serializer->serialize(
            $product,
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/products/search/{query}', name: 'app_product_search', methods: ['GET'])]
    public function findByName(string $query): JsonResponse
    {
        $products = $this->productService->findByName($query);

        $json = $this->serializer->serialize(
            $products,
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/products/price/{price}', name: 'app_product_get_by_price', requirements: ['price' => '\d+'], methods: ['GET'])]
    public function getByMaxPrice(int $price): JsonResponse
    {
        $products = $this->productService->getByMaxPrice($price);

        $json = $this->serializer->serialize(
            $products,
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/products/{id}', name: 'app_product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getById($id);

        $json = $this->serializer->serialize(
            $product,
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/products/{id}', name: 'app_product_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->productService->delete($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

}
