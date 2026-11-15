<?php

declare(strict_types=1);

namespace App\AuditBundle\EventSubscriber;

use App\AuditBundle\Entity\AuditEntry;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

final class AuditSubscriber implements EventSubscriber
{
    /**
     * Изменения, ожидающие выполнения соответствующего post-события.
     *
     * @var array<int, array{
     *     entity: object,
     *     action: string,
     *     changes: array
     * }>
     */
    private array $pendingEvents = [];

    public function __construct(
        private readonly Security $security,
        private readonly bool     $enabled,
        private readonly array    $ignoredFields,
    )
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $entityManager = $args->getObjectManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('AuditSubscriber requires Doctrine ORM EntityManager.');
        }

        $this->pendingEvents = [];

        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->collect(
                $entity,
                'CREATE',
                $unitOfWork->getEntityChangeSet($entity),
                $entityManager,
            );
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->collect(
                $entity,
                'UPDATE',
                $unitOfWork->getEntityChangeSet($entity),
                $entityManager,
            );
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $metadata = $entityManager->getClassMetadata($entity::class);

            // Сохраняем идентификатор до удаления сущности.
            $identifierValues = $metadata->getIdentifierValues($entity);

            $entityId = $identifierValues === []
                ? null
                : implode(',', array_map('strval', $identifierValues));

            // Сохраняем значения полей и связей, кроме идентификаторов.
            $changes = [];

            foreach (
                array_merge(
                    $metadata->getFieldNames(),
                    $metadata->getAssociationNames(),
                ) as $field
            ) {
                if ($metadata->isIdentifier($field)) {
                    continue;
                }

                $changes[$field] = [
                    $metadata->getFieldValue($entity, $field),
                    null,
                ];
            }

            $this->collect(
                $entity,
                'DELETE',
                $changes,
                $entityManager,
                $entityId,
            );
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->processPostEvent($args->getObject(), 'CREATE', $args->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->processPostEvent($args->getObject(), 'UPDATE', $args->getObjectManager());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->processPostEvent($args->getObject(), 'DELETE', $args->getObjectManager());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // Все post-события текущего flush уже обработаны.
        $this->pendingEvents = [];
    }

    private function collect(
        object                 $entity,
        string                 $action,
        array                  $changes,
        EntityManagerInterface $entityManager,
        ?string                $entityId = null,
    ): void
    {
        if ($entity instanceof AuditEntry) {
            return;
        }

        if ($action !== 'DELETE') {
            $metadata = $entityManager->getClassMetadata($entity::class);
            $identifierValues = $metadata->getIdentifierValues($entity);

            $entityId = $identifierValues === []
                ? null
                : implode(',', array_map('strval', $identifierValues));
        }

        $this->pendingEvents[spl_object_id($entity)] = [
            'entity' => $entity,
            'action' => $action,
            'changes' => $changes,
            'entityId' => $entityId,
        ];
    }

    private function processPostEvent(
        object $entity,
        string $action,
        object $objectManager,
    ): void
    {
        if (!$this->enabled || $entity instanceof AuditEntry) {
            return;
        }

        $objectId = spl_object_id($entity);
        $pending = $this->pendingEvents[$objectId] ?? null;

        if ($pending === null || $pending['action'] !== $action) {
            return;
        }

        if (!$objectManager instanceof EntityManagerInterface) {
            throw new \LogicException('AuditSubscriber requires Doctrine ORM EntityManager.');
        }

        $entityId = $pending['entityId'];

        if ($action === 'CREATE') {
            $metadata = $objectManager->getClassMetadata($entity::class);
            $identifierValues = $metadata->getIdentifierValues($entity);

            $entityId = $identifierValues === []
                ? null
                : implode(',', array_map('strval', $identifierValues));
        }

        $this->writeAudit(
            $objectManager,
            $entity,
            $action,
            $pending['changes'],
            $entityId,
        );

        unset($this->pendingEvents[$objectId]);
    }

    private function writeAudit(
        EntityManagerInterface $entityManager,
        object                 $entity,
        string                 $action,
        array                  $changes,
        ?string                $entityId = null,
    ): void
    {
        foreach ($this->ignoredFields as $field) {
            unset($changes[$field]);
        }

        $normalizedChanges = [];

        foreach ($changes as $field => $values) {
            $normalizedChanges[$field] = [
                'old' => $this->normalizeValue($values[0] ?? null, $entityManager),
                'new' => $this->normalizeValue($values[1] ?? null, $entityManager),
            ];
        }

        $className = $entity::class;
        $metadata = $entityManager->getClassMetadata($className);

        $entityManager->getConnection()->insert('audit_entries', [
            'username' => $this->security->getUser()?->getUserIdentifier(),
            'action' => $action,
            'entity_class' => $className,
            'entity_id' => $entityId,
            'changes' => json_encode($normalizedChanges, JSON_THROW_ON_ERROR),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    private function normalizeValue(
        mixed                  $value,
        EntityManagerInterface $entityManager,
    ): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            return array_map(
                fn(mixed $item): mixed => $this->normalizeValue($item, $entityManager),
                $value,
            );
        }

        if (is_object($value)) {
            $className = $value::class;

            if (!$entityManager->getMetadataFactory()->isTransient($className)) {
                $metadata = $entityManager->getClassMetadata($className);
                $identifiers = $metadata->getIdentifierValues($value);

                if (count($identifiers) === 1) {
                    return reset($identifiers);
                }

                if ($identifiers !== []) {
                    return $identifiers;
                }
            }

            return [
                'class' => $className,
            ];
        }

        return (string)$value;
    }
}
