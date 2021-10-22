<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Model\File\Entities\File;
use Dravencms\Database\EntityManager;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IFileRepository;

class FileRepository implements IFileRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|File */
    private $fileRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * MenuRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->fileRepository = $entityManager->getRepository(File::class);
    }

    /**
     * @param $id
     * @return mixed|null|File
     */
    public function getOneById(int $id): ?IFile
    {
        return $this->fileRepository->find($id);
    }

    /**
     * @param $sum
     * @return mixed|null|object
     */
    public function getOneBySum(string $sum): ?IFile
    {
        return $this->fileRepository->findOneBy(['sum' => $sum]);
    }

    /**
     * @param $id
     * @return File[]
     */
    public function getById($id)
    {
        return $this->fileRepository->findBy(['id' => $id]);
    }

    /**
     * @param string $sum
     * @param IFile|null $fileIgnore
     * @return bool
     */
    public function isSumFree(string $sum, IFile $fileIgnore = null): bool
    {
        $qb = $this->fileRepository->createQueryBuilder('f')
            ->select('f')
            ->where('f.sum = :sum')
            ->setParameters([
                'sum' => $sum
            ]);

        if ($fileIgnore)
        {
            $qb->andWhere('f != :fileIgnore')
                ->setParameter('fileIgnore', $fileIgnore);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @param string $sum
     * @param int $size
     * @param string $extension
     * @param string $mimeType
     * @param string $type
     * @return File|IFile
     */
    public function createNewFile(string $sum, int $size, string $extension, string $mimeType, string $type = IFile::TYPE_BINARY): IFile
    {
        $newFile = new File($sum, $size, $extension, $mimeType, $type);

        $this->entityManager->persist($newFile);
        $this->entityManager->flush();

        return $newFile;
    }

    /**
     * @param IFile $file
     * @throws \Exception
     * @return void
     */
    public function deleteFile(IFile $file): void
    {
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }

    /**
     * @param $mimeType
     * @return IFile[]
     */
    public function getByMimeType(string $mimeType)
    {
        return $this->fileRepository->findBy(['mimeType' => $mimeType]);
    }
}