<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:make-user',
    description: 'Add a short description for your command',
)]
class MakeUserCommand extends Command
{

    private $em;
    private $userRepository;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository) {
            parent::__construct();

            $this->em = $em;
            $this->passwordHasher = $passwordHasher;
            $this->userRepository = $userRepository;
        }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email:')
            ->addArgument('displayname', InputArgument::REQUIRED, 'displayname')
            ->addArgument('password', InputArgument::REQUIRED, 'password')
            ->addArgument('phone', InputArgument::REQUIRED, 'phone')
            ->addArgument('is_verified', InputArgument::OPTIONAL, 'is_verified')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=black;bg=black>Crear usuario</>');

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $displayname = $input->getArgument('displayname');
        $phone = $input->getArgument('phone');
        $is_verified= $input->getArgument('is_verified') ? 1:0;

        if($this->userRepository->findOneBy((['email' => $email]))) {
            $output->writeln("<error>El usuario con el email $email ya ha sido registratdo anteriormente</error>");

            return Command::FAILURE;
        }

        $user = (new User())->setEmail($email)
                ->setDisplayname($displayname)
                ->setPhone($phone)
                ->setIsVerified($is_verified);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln("<fg=black;bg=green>Usuario $email creado</>");
        return Command::SUCCESS;
    }
}
