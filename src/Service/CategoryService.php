<?php

namespace App\Service;

use App\Repository\CategoryRepository;

class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    )
    {
    }

    public function getAll(): array
    {
        $categories = $this->categoryRepository->findAll();

        return array_map(
            static fn($category) => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
            ],
            $categories,
        );

    }

    public function getById(int $id): ?array
    {
        $category = $this->categoryRepository->find($id);

        if ($category === null) {
            return null;
        }

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ];
    }

    public function create(string $name, ?string $description): array
    {
        $category = new \App\Entity\Category();
        $category->setName($name);
        $category->setDescription($description);

        $this->categoryRepository->save($category, true);

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ];
    }

    public function update(int $id, string $name, ?string $description): ?array
    {
        $category = $this->categoryRepository->find($id);

        if ($category === null) {
            return null;
        }

        $category->setName($name);
        $category->setDescription($description);

        $this->categoryRepository->save($category, true);

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ];
    }

    public function delete(int $id): string
    {
        $category = $this->categoryRepository->find($id);

        if ($category === null) {
            return 'not_found';
        }

        if (!$category->getProducts()->isEmpty()) {
            return 'has_products';
        }

        $this->categoryRepository->remove($category, true);

        return 'deleted';
    }
}
