<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:create-product',
    description: 'Add a short description for your command',
)]
class CreateProductCommand
{

    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
//        parent::__construct();
    }

    public function __invoke(
        InputInterface         $input,
        OutputInterface        $output
    ): int
    {
        $product = new Product();
        $product->setName('Laptop');
        $product->setPrice(1200);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $output->writeln('Product created successfully.');

        return Command::SUCCESS;
    }
}
