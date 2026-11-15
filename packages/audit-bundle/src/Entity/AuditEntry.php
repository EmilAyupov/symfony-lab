<?php

declare(strict_types=1);

namespace App\AuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_entries')]
class AuditEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 20)]
    private string $action;

    #[ORM\Column(name: 'entity_class', length: 255)]
    private string $entityClass;

    #[ORM\Column(name: 'entity_id', length: 64, nullable: true)]
    private ?string $entityId = null;

    #[ORM\Column(type: 'json')]
    private array $changes = [];

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $action,
        string $entityClass,
        ?string $entityId = null,
        ?string $username = null,
        array $changes = [],
    ) {
        $this->action = $action;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->username = $username;
        $this->changes = $changes;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
