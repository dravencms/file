<?php

namespace Dravencms\File\Console;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Latte\Runtime\Filters;
use Nette\Utils\Finder;
use Salamek\Files\FileStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class DeleteUnusedFilesCommand extends Command
{
    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    protected function configure()
    {
        $this->setName('file:unused:delete')
            ->setDescription('Deletes unused files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getHelper('container')->getByType('Dravencms\Model\File\Repository\FileRepository');

        /** @var FileStorage $fileStorage */
        $fileStorage = $this->getHelper('container')->getByType('Salamek\Files\FileStorage');

        /** @var StructureFileRepository $structureFileRepository */
        $structureFileRepository = $this->getHelper('container')->getByType('Dravencms\Model\File\Repository\StructureFileRepository');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getHelper('container')->getByType('Kdyby\Doctrine\EntityManager');

        $deletedFileStructures = 0;
        $deletedFiles = 0;
        try {
            $filesToCheckIds = [];
            foreach ($structureFileRepository->getAll() AS $structureFile) {
                // Check if we can delete that file by links
                $allAgree = [];
                foreach ($structureFile->getStructureFileLinks() AS $structureFileLink) {
                    if ($structureFileLink->isUsed() || !$structureFileLink->isAutoclean()) {
                        $allAgree[] = false;
                    } else {
                        $allAgree[] = true;
                        $entityManager->remove($structureFileLink);
                    }
                }

                $canDelete = (count(array_unique($allAgree)) === 1 && end($allAgree) === true);

                if ($canDelete) {
                    $filesToCheckIds[] = $structureFile->getFile()->getId();
                    $entityManager->remove($structureFile);
                    $deletedFileStructures++;
                }
            }

            $entityManager->flush();

            foreach($filesToCheckIds AS $filesToCheckId) {
                $fileFile = $fileRepository->getOneById($filesToCheckId);
                if (!$fileFile->getStructureFiles()->count()) {
                    $entityManager->remove($fileFile);
                    $deletedFiles++;
                }
            }

            $entityManager->flush();

            $output->writeln(sprintf('<info>Deleted file structures: %s</info>', $deletedFileStructures));
            $output->writeln(sprintf('<info>Deleted files: %s</info>', $deletedFiles));

            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}