<?php

declare(strict_types=1);

namespace App\AuditBundle\Tests\EventSubscriber;

require_once __DIR__ . '/../Fixtures/Entity/TestProduct.php';

use App\AuditBundle\Entity\AuditEntry;
use App\AuditBundle\EventSubscriber\AuditSubscriber;
use App\AuditBundle\Tests\Fixtures\Entity\TestProduct;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class AuditSubscriberTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [
                __DIR__ . '/../../src/Entity',
                __DIR__ . '/../Fixtures/Entity',
            ],
            true,
        );

        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $security = $this->createStub(Security::class);

        $eventManager = new EventManager();

        $subscriber = new AuditSubscriber(
            $security,
            true,
            ['password', 'token', 'refreshToken'],
        );

        $eventManager->addEventSubscriber($subscriber);

        $this->entityManager = new EntityManager(
            $connection,
            $config,
            $eventManager,
        );

        $metadata = [
            $this->entityManager->getClassMetadata(AuditEntry::class),
            $this->entityManager->getClassMetadata(TestProduct::class),
        ];

        (new SchemaTool($this->entityManager))->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->close();
        $this->entityManager->close();
    }

    public function testCreateAuditEntryIsRecorded(): void
    {
        $product = new TestProduct('Test product', 100);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT * FROM audit_entries WHERE entity_class = ? ORDER BY id DESC LIMIT 1',
            [TestProduct::class],
        );

        self::assertIsArray($row);
        self::assertSame('CREATE', $row['action']);
        self::assertSame((string) $product->getId(), (string) $row['entity_id']);

        $changes = json_decode($row['changes'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            ['old' => null, 'new' => 'Test product'],
            $changes['name'],
        );

        self::assertSame(
            ['old' => null, 'new' => 100],
            $changes['price'],
        );
    }

    public function testUpdateAuditEntryIsRecorded(): void
    {
        $product = new TestProduct('Original product', 100);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $product->setName('Updated product');
        $product->setPrice(200);

        $this->entityManager->flush();

        $row = $this->entityManager->getConnection()->fetchAssociative(
            "SELECT * FROM audit_entries
         WHERE entity_class = ? AND action = ?
         ORDER BY id DESC LIMIT 1",
            [TestProduct::class, 'UPDATE'],
        );

        self::assertIsArray($row);
        self::assertSame('UPDATE', $row['action']);
        self::assertSame((string) $product->getId(), (string) $row['entity_id']);

        $changes = json_decode($row['changes'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            ['old' => 'Original product', 'new' => 'Updated product'],
            $changes['name'],
        );

        self::assertSame(
            ['old' => 100, 'new' => 200],
            $changes['price'],
        );
    }

    public function testDeleteAuditEntryIsRecorded(): void
    {
        $product = new TestProduct('Product to delete', 300);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->getId();

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $row = $this->entityManager->getConnection()->fetchAssociative(
            "SELECT * FROM audit_entries
         WHERE entity_class = ? AND action = ?
         ORDER BY id DESC LIMIT 1",
            [TestProduct::class, 'DELETE'],
        );

        self::assertIsArray($row);
        self::assertSame('DELETE', $row['action']);
        self::assertSame((string) $productId, (string) $row['entity_id']);

        $changes = json_decode($row['changes'], true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('id', $changes);

        self::assertSame(
            ['old' => 'Product to delete', 'new' => null],
            $changes['name'],
        );

        self::assertSame(
            ['old' => 300, 'new' => null],
            $changes['price'],
        );
    }

    public function testSensitiveFieldsAreExcludedFromAudit(): void
    {
        $product = new TestProduct('Secure product', 500);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT * FROM audit_entries WHERE entity_class = ? ORDER BY id DESC LIMIT 1',
            [TestProduct::class],
        );

        self::assertIsArray($row);

        $changes = json_decode($row['changes'], true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('password', $changes);
        self::assertArrayNotHasKey('token', $changes);
        self::assertArrayNotHasKey('refreshToken', $changes);
    }
}
