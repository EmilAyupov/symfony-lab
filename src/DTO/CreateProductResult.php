<?php

namespace App\DTO;

use App\Entity\Product;

final class CreateProductResult
{
    /** @param array<string, string[]> $errors */
    public function __construct(
        public readonly bool $success,
        public readonly ?Product $product,
        public readonly array $errors = [],
    ) {
    }
}
