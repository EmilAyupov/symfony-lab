<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient(
            server: ['HTTP_HOST' => 'localhost:8000']
        );

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $category = new Category();
        $category->setName('Accessories');
        $category->setDescription('Test category');

        $entityManager->persist($category);
        $entityManager->flush();

        $this->categoryId = $category->getId();
    }

    public function testGetProductsReturnsSuccessfulResponse(): void
    {
        $client = $this->client;

        $client->request('GET', '/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($responseData);

        foreach ($responseData as $product) {
            $this->assertIsArray($product);
            $this->assertArrayHasKey('id', $product);
            $this->assertArrayHasKey('name', $product);
            $this->assertArrayHasKey('price', $product);
        }
    }

    public function testGetProductByIdReturnsProduct(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $createdProduct = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($createdProduct);
        $this->assertArrayHasKey('id', $createdProduct);

        $id = $createdProduct['id'];

        $client->request('GET', '/products/' . $id);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $product = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertSame($id, $product['id']);
        $this->assertSame('Test product', $product['name']);
        $this->assertSame(150, $product['price']);
    }

    public function testGetProductNotFoundReturns404(): void
    {
        $client = $this->client;

        $client->request(
            'GET',
            '/products/999999999',
            server: ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseStatusCodeSame(404);

        $product = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($product);
        $this->assertArrayHasKey('errors', $product);
        $this->assertArrayHasKey('product', $product['errors']);
        $this->assertSame(
            ['Product not found.'],
            $product['errors']['product']
        );

    }

    public function testCreateProductWithInvalidDataReturns422(): void
    {
        $client = $this->client;

        $client->request(
            'GET',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseIsSuccessful();
        $productsBeforeCount = count(json_decode($client->getResponse()->getContent(), true));

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => '',
                'price' => -10,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $product = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($product);
        $this->assertArrayHasKey('errors', $product);
        $this->assertArrayHasKey('name', $product['errors']);
        $this->assertArrayHasKey('price', $product['errors']);

        $client->request(
            'GET',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseIsSuccessful();
        $productsAfterCount = count(json_decode($client->getResponse()->getContent(), true));
        $this->assertSame($productsBeforeCount, $productsAfterCount);
    }

    public function testUpdateProductReturnsUpdatedProduct(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Original product',
                'price' => 100,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $originalProduct = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'PUT',
            '/products/' . $originalProduct['id'],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Updated product',
                'price' => 250,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(200);

        $updatedProduct = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($updatedProduct);

        $this->assertSame($originalProduct['id'], $updatedProduct['id']);
        $this->assertSame('Updated product', $updatedProduct['name']);
        $this->assertSame(250, $updatedProduct['price']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($originalProduct['id']);

        $this->assertNotNull($productFromRepository);

        $this->assertSame('Updated product', $productFromRepository->getName());
        $this->assertSame(250, $productFromRepository->getPrice());

    }


    public function testDeleteProductRemovesProduct(): void
    {
        $client = $this->client;

        // Создаём товар через API
        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Product to delete',
                'price' => 100,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $createdProduct = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $id = $createdProduct['id'];

        // Удаляем товар
        $client->request('DELETE', '/products/' . $id);

        $this->assertResponseIsSuccessful();

        // Проверяем, что товар больше не существует
        $client->request('GET', '/products/' . $id);

        $this->assertResponseStatusCodeSame(404);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($id);

        $this->assertNull($productFromRepository);
    }

    public function testDeleteProductNotFoundReturns404(): void
    {
        $client = $this->client;

        // Удаляем товар
        $client->request('DELETE', '/products/999999999');

        $this->assertResponseStatusCodeSame(404);

        $result = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('product', $result['errors']);
        $this->assertSame(
            ['Product not found.'],
            $result['errors']['product']
        );
    }

    public function testCreateProductWithValidDataReturns201(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $product = json_decode(
            $client->getResponse()->getContent(),
            true
        );


        $this->assertIsArray($product);
        $this->assertArrayHasKey('id', $product);
        $this->assertArrayHasKey('name', $product);
        $this->assertArrayHasKey('price', $product);
        $this->assertArrayHasKey('categoryName', $product);

        $this->assertSame('Test product', $product['name']);
        $this->assertSame(150, $product['price']);
        $this->assertSame('Accessories', $product['categoryName']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($product['id']);

        $this->assertNotNull($productFromRepository);

        $this->assertSame('Test product', $productFromRepository->getName());
        $this->assertSame(150, $productFromRepository->getPrice());
        $this->assertSame(
            $this->categoryId,
            $productFromRepository->getCategory()->getId()
        );
    }

    public function testUpdateProductWithInvalidDataReturns422(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $createdProduct = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $client->request(
            'PUT',
            '/products/' . $createdProduct['id'],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => '',
                'price' => -10,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('name', $response['errors']);
        $this->assertArrayHasKey('price', $response['errors']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($createdProduct['id']);

        $this->assertNotNull($productFromRepository);

        $this->assertSame('Test product', $productFromRepository->getName());
        $this->assertSame(150, $productFromRepository->getPrice());
    }

    public function testUpdateProductNotFoundReturns404(): void
    {
        $client = $this->client;

        $client->request(
            'PUT',
            '/products/999999999',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('product', $response['errors']);
        $this->assertSame(
            ['Product not found.'],
            $response['errors']['product']
        );
    }

    public function testCreateProductWithInvalidJsonReturns400(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"name": "Test product", "price":'
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
    }

    public function testCreateProductWithoutNameReturns422(): void
    {
        $client = $this->client;

        $repository = static::getContainer()->get(ProductRepository::class);
        $lastCount = $repository->count([]);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'price' => 150,
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('name', $response['errors']);

        $currentCount = $repository->count([]);

        $this->assertSame($lastCount, $currentCount);
    }

    public function testCreateProductWithoutPriceReturns422(): void
    {
        $client = $this->client;

        $repository = static::getContainer()->get(ProductRepository::class);
        $lastCount = $repository->count([]);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('price', $response['errors']);

        $currentCount = $repository->count([]);

        $this->assertSame($lastCount, $currentCount);
    }

    public function testCreateProductWithStringPriceReturns422(): void
    {
        $client = $this->client;

        $repository = static::getContainer()->get(ProductRepository::class);
        $lastCount = $repository->count([]);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 'abc',
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('price', $response['errors']);

        $currentCount = $repository->count([]);

        $this->assertSame($lastCount, $currentCount);
    }

    public function testUpdateProductWithStringPriceReturns422(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $createdProduct = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $client->request(
            'PUT',
            '/products/' . $createdProduct['id'],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'price' => 'abc',
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('price', $response['errors']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($createdProduct['id']);

        $this->assertNotNull($productFromRepository);

        $this->assertSame('Test product', $productFromRepository->getName());
        $this->assertSame(150, $productFromRepository->getPrice());
    }

    public function testCreateProductWithNullPriceReturns422(): void
    {
        $client = $this->client;

        $repository = static::getContainer()->get(ProductRepository::class);
        $lastCount = $repository->count([]);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => null,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('price', $response['errors']);

        $currentCount = $repository->count([]);

        $this->assertSame($lastCount, $currentCount);
    }

    public function testCreateProductWithZeroPrice(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Free product',
                'price' => 0,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertSame(0, $response['price']);
    }

    public function testUpdateProductWithNegativePriceReturns422(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $createdProduct = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'PUT',
            '/products/' . $createdProduct['id'],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Updated product',
                'price' => -10,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('price', $response['errors']);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($createdProduct['id']);

        $this->assertNotNull($productFromRepository);
        $this->assertSame('Test product', $productFromRepository->getName());
        $this->assertSame(150, $productFromRepository->getPrice());
    }

    public function testDeleteProductNotFoundDoesNotAffectOtherProducts(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $createdProduct = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/products/999999999');
        $this->assertResponseStatusCodeSame(404);

        $repository = static::getContainer()->get(ProductRepository::class);
        $productFromRepository = $repository->find($createdProduct['id']);

        $this->assertNotNull($productFromRepository);
        $this->assertSame('Test product', $productFromRepository->getName());
        $this->assertSame(150, $productFromRepository->getPrice());
    }

    public function testGetProductsByMaxPriceReturnsMatchingProducts(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Filter test 50',
                'price' => 50,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Filter test 150',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Filter test 300',
                'price' => 300,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/products/price/150',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($products);
        $prices = array_column($products, 'price');

        $this->assertContains(50, $prices, 'Ожидался продукт с ценой 50');
        $this->assertContains(150, $prices, 'Ожидался продукт с ценой 150');

        $this->assertNotContains(300, $prices, 'Продукт с ценой 300 не должен попадать в выборку');

    }

    public function testGetProductsByMaxPriceReturnsEmptyArray(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 50,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/products/price/1',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($products);
        $this->assertSame([], $products);
    }

    public function testGetProductsByMaxPriceIncludesExactPrice(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/products/price/150',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($products);
        $prices = array_column($products, 'price');

        $this->assertContains(150, $prices, 'Ожидался продукт с ценой 150');
    }

    public function testGetProductsByMaxPriceExcludesProductsAboveMaxPrice(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 200,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/products/price/150',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($products);
        $prices = array_column($products, 'price');

        $this->assertNotContains(200, $prices, 'Продукт с ценой выше максимальной не должен попадать в результат');
    }

    public function testGetProductsByMaxPriceWithInvalidPriceReturns500(): void
    {
        $client = $this->client;

        $client->request(
            'GET',
            '/products/price/abs',
        );

        $this->assertResponseStatusCodeSame(500);
    }

    public function testGetProductsByMaxPriceWithNegativePriceReturns500(): void
    {
        $client = $this->client;

        $client->request(
            'GET',
            '/products/price/-10',
        );

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertResponseStatusCodeSame(500);
    }

    public function testGetProductsByMaxPriceWithZeroReturnsProducts(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Test product',
                'price' => 0,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/products/price/0',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($products);
        $prices = array_column($products, 'price');

        $this->assertContains(0, $prices);
    }

    public function testGetProductsByMaxPriceReturnsProductsInAscendingPriceOrder(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Filter test 30',
                'price' => 30,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Filter test 50',
                'price' => 50,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Filter test 150',
                'price' => 150,
                'categoryId' => $this->categoryId,
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/products/price/300',
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');

        $products = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($products);
        $prices = array_column($products, 'price');

        $sortedPrices = $prices;
        sort($sortedPrices);

        $this->assertSame(
            $sortedPrices,
            $prices,
            'Продукты должны возвращаться в порядке возрастания цены'
        );
    }

}
