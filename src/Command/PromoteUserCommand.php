<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:promote-admin',
    description: 'Grant ROLE_ADMIN to an existing user',
)]
class PromoteUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            $output->writeln('<error>User not found.</error>');

            return Command::FAILURE;
        }

        $roles = $user->getRoles();

        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $this->entityManager->flush();
        }

        $output->writeln(sprintf(
            '<info>ROLE_ADMIN granted to %s.</info>',
            $email,
        ));

        return Command::SUCCESS;
    }
}
