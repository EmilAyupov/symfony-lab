<?php

namespace App\Service;

use App\DTO\CreateProductRequest;
use App\DTO\CreateProductResult;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductService
{
    public function __construct(
        private ProductRepository      $productRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface     $validator,
        private LoggerInterface        $logger,
        private CategoryRepository     $categoryRepository,
    )
    {
    }

    public function getAll(): array
    {
        return $this->productRepository->findAll();
    }

    public function getById(int $id): ?Product
    {
        $product = $this->productRepository->find($id);
        if ($product === null) {
            throw new ProductNotFoundException("Product not found.");
        }
        return $product;
    }

    public function findByName(string $name): array
    {
        return $this->productRepository->findByName($name);
    }

    public function getByMaxPrice(int $maxPrice): array
    {
        if ($maxPrice < 0) {
            return [];
        }

        return $this->productRepository->findByMaxPrice($maxPrice);
    }

    public function create(CreateProductRequest $data): Product
    {
        $errors = $this->validator->validate($data);

        if (count($errors) > 0) {
            throw new ValidationException(
                $this->formatValidationErrors($errors)
            );
        }

        $category = $this->categoryRepository->find($data->categoryId);

        if ($category === null) {
            throw new ValidationException([
                'categoryId' => ['Category not found.'],
            ]);
        }

        $product = new Product();
        $product->setName($data->name);
        $product->setPrice($data->price);
        $product->setCategory($category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->logger->info('Товар создан', [
            'product_id' => $product->getId(),
        ]);

        return $product;
    }

    public function update(
        int                  $id,
        CreateProductRequest $data
    ): Product
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            throw new ProductNotFoundException('Product not found.');
        }

        $errors = $this->validator->validate($data);

        if (count($errors) > 0) {
            throw new ValidationException(
                $this->formatValidationErrors($errors)
            );
        }

        $category = $this->categoryRepository->find($data->categoryId);

        if ($category === null) {
            throw new ValidationException([
                'categoryId' => ['Category not found.'],
            ]);
        }

        $product->setName($data->name);
        $product->setPrice($data->price);
        $product->setCategory($category);

        $this->entityManager->flush();

        $this->logger->info('Товар обновлен', [
            'product_id' => $product->getId(),
        ]);

        return $product;
    }

    public function delete(
        int $id,
    ): void
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            throw new ProductNotFoundException('Product not found.');
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->logger->info('Товар удален', [
            'product_id' => $id,
        ]);
    }

    /**
     * @return array<string, string[]>
     */
    private function formatValidationErrors(
        ConstraintViolationListInterface $errors
    ): array
    {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $errorMessages;
    }

    public function getByCategoryId(int $categoryId): array
    {
        return $this->productRepository->findByCategoryId($categoryId);
    }
}

