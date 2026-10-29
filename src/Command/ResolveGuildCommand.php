<?php

namespace App\Command;

use App\Repository\GuildResolverRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:guild:resolve', description: 'Prints the guild name for one or more guild ids')]
final class ResolveGuildCommand extends Command
{
    public function __construct(private readonly GuildResolverRepository $repository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('guild_id', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Guild id(s)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ids = $input->getArgument('guild_id');
        $names = $this->repository->resolveNames($ids);
        $status = Command::SUCCESS;

        foreach ($ids as $id) {
            if (isset($names[$id])) {
                $output->writeln(sprintf('%s => %s', $id, $names[$id]));
            } else {
                $output->writeln(sprintf('<error>%s => unknown guild</error>', $id));
                $status = Command::FAILURE;
            }
        }

        return $status;
    }
}
