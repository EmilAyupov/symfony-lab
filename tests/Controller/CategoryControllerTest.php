<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    private ?int $categoryId = null;

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        if ($this->categoryId !== null) {
            $category = $entityManager->find(Category::class, $this->categoryId);

            if ($category !== null) {
                $entityManager->remove($category);
                $entityManager->flush();
            }
        }

        parent::tearDown();
    }

    public function testGetCategoriesReturnsJsonList(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $category = new Category();
        $category->setName('Category controller test');
        $category->setDescription('Test category description');

        $entityManager->persist($category);
        $entityManager->flush();

        $this->categoryId = $category->getId();

        $client->request('GET', '/categories');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame(
            'content-type',
            'application/json'
        );

        $responseData = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertIsArray($responseData);

        $matchingCategories = array_values(array_filter(
            $responseData,
            fn(array $item): bool => $item['id'] === $this->categoryId
        ));

        $this->assertCount(1, $matchingCategories);
        $this->assertSame(
            'Category controller test',
            $matchingCategories[0]['name']
        );
        $this->assertSame(
            'Test category description',
            $matchingCategories[0]['description']
        );
    }
}
