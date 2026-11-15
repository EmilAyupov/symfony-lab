<?php

namespace App\Tests\Service;

use App\DTO\CreateProductRequest;
use App\Entity\Category;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Exception\ValidationException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductServiceTest extends TestCase
{
    private CategoryRepository $categoryRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryRepository = $this->createStub(CategoryRepository::class);
    }

    public function testGetByMaxPriceWithNegativeValueReturnsEmptyArrayAndDoesNotCallRepository(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);

        // Репозиторий НЕ должен вызываться при отрицательной цене
        $repository
            ->expects($this->never())
            ->method('findByMaxPrice');

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Act
        $result = $service->getByMaxPrice(-10);

        // Assert
        $this->assertSame([], $result);
    }

    public function testGetByMaxPriceWithPositiveValueCallsRepositoryAndReturnsResult(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);
        $product = $this->createStub(Product::class);
        // Проверяем вызов репозитория с ценой 100
        $repository
            ->expects($this->once())
            ->method('findByMaxPrice')
            ->with(100)
            ->willReturn([$product]);

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Act
        $result = $service->getByMaxPrice(100);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame($product, $result[0]);
    }

    public function testGetByMaxPriceWithZeroValueCallsRepositoryAndReturnsResult(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);
        $product = $this->createStub(Product::class);
        // Проверяем вызов репозитория с ценой 100
        $repository
            ->expects($this->once())
            ->method('findByMaxPrice')
            ->with(0)
            ->willReturn([$product]);

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Act
        $result = $service->getByMaxPrice(0);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame($product, $result[0]);
    }

    public function testGetByIdReturnsProduct(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);
        $product = $this->createStub(Product::class);

        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($product);

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Act
        $result = $service->getById(123);

        // Assert
        $this->assertSame($product, $result);
    }

    public function testGetByIdThrowsExceptionWhenProductNotFound(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);

        $repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Assert — сначала задаём ожидания
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product not found.');

        // Act — затем вызываем метод
        $service->getById(999);
    }

    public function testGetAllReturnsProducts(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);
        $product1 = $this->createStub(Product::class);
        $product2 = $this->createStub(Product::class);

        $repository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$product1, $product2]);

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Act
        $result = $service->getAll();

        // Assert
        $this->assertSame([$product1, $product2], $result);
    }

    public function testFindByNameReturnsProducts(): void
    {
        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);
        $product1 = $this->createStub(Product::class);
        $product2 = $this->createStub(Product::class);

        $repository
            ->expects($this->once())
            ->method('findByName')
            ->with('Laptop')
            ->willReturn([$product1, $product2]);

        // Собираем сервис с стабами
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        // Act
        $result = $service->findByName('Laptop');

        // Assert
        $this->assertSame([$product1, $product2], $result);
    }

    public function testCreateWithValidDataPersistsProduct(): void
    {
        // Создаём мок репозитория
        $repository = $this->createStub(ProductRepository::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $request = new CreateProductRequest('Test Product', 150, 1);

        $category = new Category();
        $category->setName('Accessories');
        $category->setDescription('Test category');

        $categoryRepository = $this->createMock(CategoryRepository::class);

        $categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $violations = new ConstraintViolationList();

        // Валидатор должен получить DTO и вернуть пустой список ошибок
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations);

        // EntityManager должен сохранить новый Product
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $categoryRepository,
        );

        // Act: вызываем сервис
        $result = $service->create($request);

        // Assert: проверяем результат
        $this->assertInstanceOf(Product::class, $result);
        $this->assertSame('Test Product', $result->getName());
        $this->assertSame(150, $result->getPrice());
    }

    public function testCreateWithInvalidDataThrowsValidationException(): void
    {
        // Создаём мок репозитория
        $repository = $this->createStub(ProductRepository::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $request = new CreateProductRequest('', -10, 1);

        $violation = new ConstraintViolation(
            'Product name cannot be empty.',
            null,
            [],
            null,
            'name',
            ''
        );
        $violations = new ConstraintViolationList([$violation]);

        // Валидатор должен получить DTO и вернуть список нарушений
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations);

        // При ошибке валидации продукт не должен сохраняться
        $entityManager
            ->expects($this->never())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));

        $entityManager
            ->expects($this->never())
            ->method('flush');

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        try {
            $service->create($request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('Validation failed.', $exception->getMessage());
            $this->assertSame(
                ['name' => ['Product name cannot be empty.']],
                $exception->errors
            );
        }
    }

    public function testUpdateWithValidDataUpdatesProduct(): void
    {
        // Arrange: создаём существующий продукт
        $product = new Product();
        $product->setName('Old Product');
        $product->setPrice(100);

        $repository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $request = new CreateProductRequest('Updated Product', 200, 1);

        $category = new Category();
        $category->setName('Accessories');
        $category->setDescription('Test category');

        $categoryRepository = $this->createMock(CategoryRepository::class);

        $categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        // Репозиторий должен найти существующий продукт
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($product);

        // Валидатор должен принять DTO без ошибок
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn(new ConstraintViolationList());

        // Существующий продукт должен быть сохранён
        $entityManager
            ->expects($this->once())
            ->method('flush');

        // Act: обновляем продукт
        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $categoryRepository,
        );

        $result = $service->update(123, $request);

        // Assert: проверяем результат обновления
        $this->assertSame($product, $result);
        $this->assertSame('Updated Product', $result->getName());
        $this->assertSame(200, $result->getPrice());
    }

    public function testUpdateThrowsExceptionWhenProductNotFound(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        // Продукт с таким ID не найден
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        // При отсутствии продукта валидация не должна запускаться
        $validator
            ->expects($this->never())
            ->method('validate');

        // Никаких изменений в БД быть не должно
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        $request = new CreateProductRequest('Updated Product', 200, 1);

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product not found.');

        $service->update(999, $request);
    }

    public function testDeleteRemovesProduct(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice(100);

        $repository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        // Репозиторий должен найти продукт
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($product);

        // Продукт должен быть удалён
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($product);

        // Изменения должны быть сохранены
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        $service->delete(123);
    }

    public function testDeleteThrowsExceptionWhenProductNotFound(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        // Репозиторий должен не найти продукт
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        // Удаление и сохранение не должны вызываться
        $entityManager
            ->expects($this->never())
            ->method('remove');

        $entityManager
            ->expects($this->never())
            ->method('flush');

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product not found.');

        $service->delete(123);
    }

    public function testUpdateWithInvalidDataThrowsValidationException(): void
    {
        $product = new Product();
        $product->setName('Old Product');
        $product->setPrice(100);

        // Создаём мок репозитория
        $repository = $this->createMock(ProductRepository::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $request = new CreateProductRequest('', -10, 1);

        $violation = new ConstraintViolation(
            'Product name cannot be empty.',
            null,
            [],
            null,
            'name',
            ''
        );
        $violations = new ConstraintViolationList([$violation]);

        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($product);

        // Валидатор должен получить DTO и вернуть список нарушений
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations);

        // При ошибке валидации продукт не должен сохраняться
        $entityManager
            ->expects($this->never())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));

        $entityManager
            ->expects($this->never())
            ->method('flush');

        $service = new ProductService(
            $repository,
            $entityManager,
            $validator,
            $logger,
            $this->categoryRepository,
        );

        try {
            $service->update(123, $request);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('Old Product', $product->getName());
            $this->assertSame(100, $product->getPrice());
            $this->assertSame('Validation failed.', $exception->getMessage());
            $this->assertSame(
                ['name' => ['Product name cannot be empty.']],
                $exception->errors
            );
        }
    }
}
