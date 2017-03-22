<?php

namespace Dravencms\File\Console;

use Dravencms\Model\File\Repository\FileRepository;
use Kdyby\Doctrine\EntityManager;
use Salamek\Files\FileStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class FixExtensionsCommand extends Command
{
    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    private $fixTable = [
        'image/jpeg' => 'jpg' //I rather use jpeg, but windows ruins everything...
    ];

    protected function configure()
    {
        $this->setName('file:extension:fix')
            ->setDescription('Fixes corrupted extensions for known files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getHelper('container')->getByType('Dravencms\Model\File\Repository\FileRepository');

        /** @var FileStorage $fileStorage */
        $fileStorage = $this->getHelper('container')->getByType('Salamek\Files\FileStorage');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getHelper('container')->getByType('Kdyby\Doctrine\EntityManager');

        try {
            $cnt = 0;
            foreach ($this->fixTable AS $mimeType => $extension)
            {
                foreach($fileRepository->getByMimeType($mimeType) AS $file)
                {
                    if (is_file($fileStorage->getFileSystemPath($file)))
                    {
                        if ($file->getExtension() != $extension)
                        {
                            $output->writeln('<info>Found file with wrong extension '.$file->getExtension().' fixing to  '.$extension.'</info>');
                            $newName = $fileStorage->getDataDir().'/'.$file->getSum().'.'.$extension;
                            if (is_file($newName))
                            {
                                $output->writeln('<warning>New file found on FS, removing the old one</warning>');
                                // Already on FS, just remove old and continue to DB
                                unlink($newName);
                            }
                            else
                            {
                                $output->writeln('<info>Renaming file to match new extension</info>');
                                rename ($fileStorage->getFileSystemPath($file), $newName);
                            }


                            $file->setExtension($extension);
                            $entityManager->persist($file);
                            $entityManager->flush();
                            $cnt++;
                        }
                    }
                    else
                    {
                        $output->writeln('<warning>' . $fileStorage->getFileSystemPath($file) . ' not found, ignoring...</warning>');
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