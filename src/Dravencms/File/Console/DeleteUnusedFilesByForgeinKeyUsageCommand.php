<?php

namespace Dravencms\File\Console;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\Query;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Doctrine\DBAL\Connection;
use Kdyby\Doctrine\Registry;
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

class DeleteUnusedFilesByForgeinKeyUsageCommand extends Command
{
    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    protected function configure()
    {
        $this->setName('file:unused-f:delete')
            ->setDescription('Deletes unused files - detection by forgein key exception...');
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

        /** @var Registry $registry */
        $registry = $this->getHelper('container')->getByType('Kdyby\Doctrine\Registry');

        try {
            $deletedFiles = 0;
            $deletedStructureFiles = 0;
            $usedStructureFiles = 0;
            $connection = $entityManager->getConnection();
            $filesToCheckIds = [];
            foreach ($structureFileRepository->getAll() AS $structureFile) {
                $connection->beginTransaction();
              try {
                  $statement = $connection->prepare('DELETE FROM fileStructureFile WHERE id=?');
                  $statement->execute([$structureFile->getId()]);
                  $entityManager->getConnection()->commit();
                  $filesToCheckIds[] = $structureFile->getFile()->getId();
                  $deletedStructureFiles++;
              } catch (ForeignKeyConstraintViolationException $e) {
                  $connection->rollBack();
                  $usedStructureFiles++;
              }
            }
            
            foreach($filesToCheckIds AS $filesToCheckId) {
                $fileFile = $fileRepository->getOneById($filesToCheckId);
                if (!$fileFile->getStructureFiles()->count()) {
                    $entityManager->remove($fileFile);
                    $deletedFiles++;
                }
            }
            
            $output->writeln(sprintf('<info>Deleted structure files: %s</info>', $deletedStructureFiles));
            $output->writeln(sprintf('<info>Used structure files: %s</info>', $usedStructureFiles));
            $output->writeln(sprintf('<info>Deleted files: %s</info>', $deletedFiles));
            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}
