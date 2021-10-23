<?php declare(strict_types = 1);

namespace Dravencms\File\Console;

use Dravencms\Model\File\Repository\FileRepository;
use Latte\Runtime\Filters;
use Nette\Utils\Finder;
use Salamek\Files\FileStorage;
use Salamek\Files\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class MigrateToSegmentedDataDirCommand extends Command
{
    protected static $defaultName = 'file:segmented-storage:migrate';
    protected static $defaultDescription = 'Migrates from old storage format to segmented format';

    const ACTION_NO = 'n';
    const ACTION_YES = 'y';


    /** @var FileRepository */
    private $fileRepository;

    /** @var FileStorage */
    private $fileStorage;

    public function __construct(FileRepository $fileRepository, FileStorage $fileStorage)
    {
        $this->fileRepository = $fileRepository;
        $this->fileStorage = $fileStorage;
        parent::__construct(null);
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $toDelete = [];
            $toDeleteSize = 0;

            foreach (Finder::findFiles('*')->exclude('README.md')->in($this->fileStorage->getDataDir()) as $key => $file) {
                $fileSum = $file->getBasename('.' . $file->getExtension());
                $foundFile = $this->fileRepository->getOneBySum($fileSum);

                if ($foundFile) {
                    $output->writeln(sprintf('<comment>Migrating file: %s</comment>', $file->getFileName()));

                    if (!is_dir($this->fileStorage->getDataDirForFile($foundFile)))
                    {
                        Tools::mkdir($this->fileStorage->getDataDirForFile($foundFile));
                    }

                    if (!@rename($file->getPathName(), $this->fileStorage->getFileSystemPath($foundFile)))
                    {
                        throw new \Exception(sprintf('Failed to move file %s, is it writable ?', $file->getPathName()));
                    }
                } else {
                    $output->writeln(sprintf('<comment>Marking for deletion: %s</comment>', $file->getFileName()));
                    $toDelete[] = $file;
                    $toDeleteSize += $file->getSize();
                }
            }

            if (empty($toDelete))
            {
                $output->writeln('<info>Nothing to delete, exiting...</info>');
                return 0;
            }
            

            $helper = $this->getHelper('question');
            $question = new Question(sprintf('%s files are marked for deletion and %s will be freed, do you wish to continue (y/n=default)', count($toDelete), Filters::bytes($toDeleteSize)),
                self::ACTION_NO);
            $action = $helper->ask($input, $output, $question);

            switch ($action) {
                case self::ACTION_YES:
                    $progress = new ProgressBar($output, count($toDelete));
                    $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message:6s%');
                    $progress->setMessage('Starting...');
                    $progress->start();
                    foreach($toDelete AS $file)
                    {
                        $progress->setMessage(sprintf('Deleting %s', $file->getFileName()));
                        if (!@unlink($file->getPathName()))
                        {
                            throw new \Exception(sprintf('Failed to delete file %s, is it writable ?', $file->getPathName()));
                        }
                        $progress->advance();
                    }
                    $progress->finish();
                    $output->writeln('<info>All ophranded files has been successfully deleted</info>');
                    break;
                case self::ACTION_NO:
                    $output->writeln('<info>Exiting...with no changes</info>');
                    return 0;
                    break;
            }

            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}
