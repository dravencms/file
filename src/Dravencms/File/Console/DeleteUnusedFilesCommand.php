<?php declare(strict_types = 1);

namespace Dravencms\File\Console;

use Dravencms\Database\EntityManager;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Dravencms\Model\File\Repository\StructureRepository;
use Salamek\Files\FileStorage;
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

    /** @var FileStorage */
    private $fileStorage;

    /** @var StructureFileRepository  */
    private $structureFileRepository;

    /** @var StructureRepository */
    private $structureRepository;

    /**
     * DeleteUnusedFilesCommand constructor.
     * @param EntityManager $entityManager
     * @param FileRepository $fileRepository
     * @param FileStorage $fileStorage
     * @param StructureFileRepository $structureFileRepository
     * @param StructureRepository $structureRepository
     */
    public function __construct(
        EntityManager $entityManager,
        FileRepository $fileRepository,
        FileStorage $fileStorage,
        StructureFileRepository $structureFileRepository,
        StructureRepository $structureRepository
    )
    {
        parent::__construct(null);
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
        $this->fileStorage = $fileStorage;
        $this->structureFileRepository = $structureFileRepository;
        $this->structureRepository = $structureRepository;
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
        $deletedStructures = 0;
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


            foreach ($this->structureRepository->getAll() AS $structure) {
                $info = $this->fileStorage->getStructureFilesInfo($structure);
                if ($info['files'] == 0 && $info['folders'] == 0) {
                    # Structure is empty, delete it
                    $this->entityManager->remove($structure);
                    $deletedStructures++;
                }
            }

            $this->entityManager->flush();

            $output->writeln(sprintf('<info>Deleted file structures: %s</info>', $deletedFileStructures));
            $output->writeln(sprintf('<info>Deleted files: %s</info>', $deletedFiles));
            $output->writeln(sprintf('<info>Deleted structures: %s</info>', $deletedStructures));

            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}