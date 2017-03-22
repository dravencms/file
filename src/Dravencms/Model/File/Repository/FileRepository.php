<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Model\File\Entities\File;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IFileRepository;

class FileRepository implements IFileRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
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
    public function getOneById($id)
    {
        return $this->fileRepository->find($id);
    }

    /**
     * @param $sum
     * @return mixed|null|object
     */
    public function getOneBySum($sum)
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
     * @param $sum
     * @param IFile|null $fileIgnore
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isSumFree($sum, IFile $fileIgnore = null)
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
     * @param $md5
     * @param $size
     * @param $extension
     * @param $mimeType
     * @param string $type
     * @return File
     */
    public function createNewFile($md5, $size, $extension, $mimeType, $type = IFile::TYPE_BINARY)
    {
        $newFile = new File($md5, $size, $extension, $mimeType, $type);

        $this->entityManager->persist($newFile);
        $this->entityManager->flush();

        return $newFile;
    }

    /**
     * @param IFile $file
     * @throws \Exception
     * @return void
     */
    public function deleteFile(IFile $file)
    {
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }

    /**
     * @param $mimeType
     * @return IFile[]
     */
    public function getByMimeType($mimeType)
    {
        return $this->fileRepository->findBy(['mimeType' => $mimeType]);
    }
}