<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Product name cannot be empty.')]
        #[Assert\Type('string')]
        #[Assert\Length(max: 255)]
        public readonly mixed $name,

        #[Assert\NotNull(message: 'Product price is required.')]
        #[Assert\Type('integer', message: 'Product price must be an integer.')]
        #[Assert\PositiveOrZero(message: 'Product price cannot be negative.')]
        public readonly mixed $price,

        #[Assert\NotNull(message: 'Product category is required.')]
        #[Assert\Type(type: 'integer', message: 'Product category ID must be an integer.')]
        #[Assert\Positive(message: 'Product category ID must be positive.')]
        public readonly mixed $categoryId,
    ) {
    }
}
