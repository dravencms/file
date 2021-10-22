<?php declare(strict_types = 1);

namespace Dravencms\File\Console;

use Dravencms\Database\EntityManager;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class DeleteUnusedFilesCommand extends Command
{
    protected static $defaultName = 'file:unused:delete';
    protected static $defaultDescription = 'Deletes unused files';

    const ACTION_NO = 'n';
    const ACTION_YES = 'y';

    /** @var EntityManager */
    private $entityManager;

    /** @var FileRepository */
    private $fileRepository;

    /** @var StructureFileRepository  */
    private $structureFileRepository;

    /**
     * DeleteUnusedFilesCommand constructor.
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deletedFileStructures = 0;
        $deletedFiles = 0;
        try {
            $filesToCheckIds = [];
            foreach ($this->structureFileRepository->getAll() AS $structureFile) {
                // Check if we can delete that file by links
                $allAgree = [];
                foreach ($structureFile->getStructureFileLinks() AS $structureFileLink) {
                    if ($structureFileLink->isUsed() || !$structureFileLink->isAutoclean()) {
                        $allAgree[] = false;
                    } else {
                        $allAgree[] = true;
                        $this->entityManager->remove($structureFileLink);
                    }
                }

                $canDelete = (count(array_unique($allAgree)) === 1 && end($allAgree) === true);

                if ($canDelete) {
                    $filesToCheckIds[] = $structureFile->getFile()->getId();
                    $this->entityManager->remove($structureFile);
                    $deletedFileStructures++;
                }
            }

            $this->entityManager->flush();

            foreach($filesToCheckIds AS $filesToCheckId) {
                $fileFile = $this->fileRepository->getOneById($filesToCheckId);
                if (!$fileFile->getStructureFiles()->count()) {
                    $this->entityManager->remove($fileFile);
                    $deletedFiles++;
                }
            }

            $this->entityManager->flush();

            $output->writeln(sprintf('<info>Deleted file structures: %s</info>', $deletedFileStructures));
            $output->writeln(sprintf('<info>Deleted files: %s</info>', $deletedFiles));

            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}