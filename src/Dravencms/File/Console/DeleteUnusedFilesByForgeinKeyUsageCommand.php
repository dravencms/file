<?php declare(strict_types = 1);

namespace Dravencms\File\Console;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Dravencms\Database\EntityManager;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class DeleteUnusedFilesByForgeinKeyUsageCommand extends Command
{
    protected static $defaultName = 'file:unused-f:delete';
    protected static $defaultDescription = 'Deletes unused files - detection by forgein key exception...';

    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    /** @var EntityManager */
    private $entityManager;

    /** @var FileRepository */
    private $fileRepository;

    /** @var StructureFileRepository */
    private $structureFileRepository;

    /**
     * DeleteUnusedFilesByForgeinKeyUsageCommand constructor.
     * @param EntityManager $entityManager
     * @param FileRepository $fileRepository
     * @param StructureFileRepository $structureFileRepository
     */
    public function __construct(EntityManager $entityManager, FileRepository $fileRepository, StructureFileRepository $structureFileRepository)
    {
        parent::__construct(null);
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
        $this->structureFileRepository = $structureFileRepository;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $deletedFiles = 0;
            $deletedStructureFiles = 0;
            $usedStructureFiles = 0;
            $connection = $this->entityManager->getConnection();
            $filesToCheckIds = [];
            foreach ($this->structureFileRepository->getAll() AS $structureFile) {
                $connection->beginTransaction();
              try {
                  $statement = $connection->prepare('DELETE FROM fileStructureFile WHERE id=?');
                  $statement->executeStatement([$structureFile->getId()]);
                  $this->entityManager->getConnection()->commit();
                  $filesToCheckIds[] = $structureFile->getFile()->getId();
                  $deletedStructureFiles++;
              } catch (ForeignKeyConstraintViolationException $e) {
                  $connection->rollBack();
                  $usedStructureFiles++;
              }
            }
            
            foreach($filesToCheckIds AS $filesToCheckId) {
                $fileFile = $this->fileRepository->getOneById($filesToCheckId);
                if (!$fileFile->getStructureFiles()->count()) {
                    $this->entityManager->remove($fileFile);
                    $deletedFiles++;
                }
            }
            
            $this->entityManager->flush();
            
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
