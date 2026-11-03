<?php declare(strict_types=1);

namespace Dravencms\File\Console;

use Dravencms\File\FileHashSchemaMigration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateFileHashCommand extends Command
{
    public function __construct(private FileHashSchemaMigration $migration) { parent::__construct('file:hash:migrate'); }

    protected function configure(): void
    {
        $this->setDescription('Preview the file checksum column migration; use --apply to execute it')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Expand the file checksum column to 64 characters');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->migration->getSql() as $sql) { $output->writeln($sql . ';', OutputInterface::OUTPUT_RAW); }
        if ($input->getOption('apply')) { $this->migration->apply(); $output->writeln('File checksum schema is ready.'); }
        return Command::SUCCESS;
    }
}
