<?php declare(strict_types = 1);

namespace Dravencms\File\Console;

use Dravencms\Database\EntityManager;
use Dravencms\Model\File\Repository\FileRepository;
use Salamek\Files\FileStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class FixExtensionsCommand extends Command
{
    protected static $defaultName = 'file:extension:fix';
    protected static $defaultDescription = 'Fixes corrupted extensions for known files';

    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    private $fixTable = [
        'image/jpeg' => 'jpg' //I rather use jpeg, but windows ruins everything...
    ];

    /** @var EntityManager */
    private $entityManager;

    /** @var FileRepository */
    private $fileRepository;

    /** @var FileStorage */
    private $fileStorage;

    public function __construct(EntityManager $entityManager, FileRepository $fileRepository, FileStorage $fileStorage)
    {
        parent::__construct(null);
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
        $this->fileStorage = $fileStorage;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $cnt = 0;
            foreach ($this->fixTable AS $mimeType => $extension)
            {
                foreach($this->fileRepository->getByMimeType($mimeType) AS $file)
                {
                    if (is_file($this->fileStorage->getFileSystemPath($file)))
                    {
                        if ($file->getExtension() != $extension)
                        {
                            $output->writeln('<info>Found file with wrong extension '.$file->getExtension().' fixing to  '.$extension.'</info>');
                            $newName = $this->fileStorage->getDataDir().'/'.$file->getSum().'.'.$extension;
                            if (is_file($newName))
                            {
                                $output->writeln('<warning>New file found on FS, removing the old one</warning>');
                                // Already on FS, just remove old and continue to DB
                                unlink($newName);
                            }
                            else
                            {
                                $output->writeln('<info>Renaming file to match new extension</info>');
                                rename ($this->fileStorage->getFileSystemPath($file), $newName);
                            }


                            $file->setExtension($extension);
                            $this->entityManager->persist($file);
                            $this->entityManager->flush();
                            $cnt++;
                        }
                    }
                    else
                    {
                        $output->writeln('<warning>' . $this->fileStorage->getFileSystemPath($file) . ' not found, ignoring...</warning>');
                    }
                }
            }

            $output->writeln('<info>'.$cnt.' files fixed</info>');
            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}