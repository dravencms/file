<?php

namespace Dravencms\File\Console;

use Dravencms\Model\File\Repository\FileRepository;
use Latte\Runtime\Filters;
use Nette\Utils\Finder;
use Salamek\Files\FileStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class DeleteOrphanedFilesCommand extends Command
{
    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    protected function configure()
    {
        $this->setName('file:orphaned:delete')
            ->setDescription('Deletes ophranded files');
        
        $this->addOption('yes', null, InputOption::VALUE_OPTIONAL, 'Auto YES', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getHelper('container')->getByType('Dravencms\Model\File\Repository\FileRepository');

        /** @var FileStorage $fileStorage */
        $fileStorage = $this->getHelper('container')->getByType('Salamek\Files\FileStorage');

        try {

            $toDelete = [];
            $toDeleteSize = 0;
            foreach (Finder::findFiles('*')->exclude('README.md')->in($fileStorage->getDataDir()) as $key => $file) {
                if ($fileRepository->isSumFree($file->getBasename('.'.$file->getExtension())))
                {
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
            
            $allYes = $input->getOption('yes');
            
            if (!$allYes) {
                $helper = $this->getHelper('question');
                $question = new Question(sprintf('%s files are marked for deletion and %s will be freed, do you wish to continue (y/n=default)', count($toDelete), Filters::bytes($toDeleteSize)),
                    self::ACTION_NO);
                $action = $helper->ask($input, $output, $question);
            } else {
                $action = elf::ACTION_YES;
            }

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
