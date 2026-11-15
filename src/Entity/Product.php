<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Product name cannot be empty.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Product name cannot exceed {{ limit }} characters.'
    )]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Product price is required.')]
    #[Assert\Type(
        type: 'integer',
        message: 'Product price must be an integer.'
    )]
    #[Assert\PositiveOrZero(message: 'Product price cannot be negative.')]
    private ?int $price = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[Groups(['product:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['product:read'])]
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(['product:read'])]
    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    #[Groups(['product:read'])]
    public function getCategoryName(): ?string
    {
        return $this->category?->getName();
    }
}
