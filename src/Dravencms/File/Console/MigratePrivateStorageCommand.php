<?php declare(strict_types=1);
namespace Dravencms\File\Console;
use Salamek\Files\{FileStorage, StorageMigration};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

final class MigratePrivateStorageCommand extends Command
{
    protected static $defaultName = 'file:storage:migrate';
    protected static $defaultDescription = 'Preview/move public originals and old previews to private storage';
    public function __construct(private FileStorage $storage, private string $wwwDir) { parent::__construct(); }
    protected function configure(): void { $this->addOption('apply', null, InputOption::VALUE_NONE, 'Move files after full preflight and verify their SHA-256 checksums'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $directories = [$this->wwwDir . '/data' => $this->storage->getDataDir(), $this->wwwDir . '/webtemp' => dirname($this->storage->getWebTempDir()) . '/legacy-webtemp'];
            $keep = array_merge(glob($this->wwwDir . '/webtemp/*.css'), glob($this->wwwDir . '/webtemp/*.js'));
            $migration = new StorageMigration(); $plan = $migration->plan($directories, $keep);
            $output->writeln('Files to move outside the web root: ' . count($plan));
            if (!$input->getOption('apply')) { $output->writeln('Preview only. Stop uploads and block old /data and image-preview routes before running with --apply.'); return 0; }
            $output->writeln('Moved and checksum-verified files: ' . $migration->apply($directories, $keep));
            return 0;
        } catch (\Throwable $e) { $output->writeln('<error>' . $e->getMessage() . '</error>'); return 1; }
    }
}
