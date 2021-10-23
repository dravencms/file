<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Database\EntityManager;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IStructure;
use Salamek\Files\Models\IStructureFile;
use Salamek\Files\Models\IStructureFileRepository;

class StructureFileRepository implements IStructureFileRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|StructureFile */
    private $structureFileRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * MenuRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->structureFileRepository = $entityManager->getRepository(StructureFile::class);
    }

    /**
     * @param $id
     * @return mixed|null|StructureFile
     */
    public function getOneById(int $id): ?IStructureFile
    {
        return $this->structureFileRepository->find($id);
    }

    /**
     * @param $id
     * @return StructureFile[]
     */
    public function getById($id)
    {
        return $this->structureFileRepository->findBy(['id' => $id]);
    }

    /**
     * @return StructureFile[]
     */
    public function getAll()
    {
        return $this->structureFileRepository->findAll();
    }

    /**
     * @param IStructure|null $structure
     * @return StructureFile[]
     */
    public function getByStructure(IStructure $structure = null)
    {
        return $this->structureFileRepository->findBy(['structure' => $structure]);
    }

    /**
     * @param IStructure|null $structure
     * @param string|null $type
     * @return IStructureFile[]
     */
    public function getByStructureAndType(IStructure $structure = null, string $type = null)
    {
        $qb = $this->structureFileRepository->createQueryBuilder('sf')
            ->select('sf')
            ->join('sf.file', 'f');;

        if ($type)
        {
            $qb->andWhere('f.type = :type')
                ->setParameter('type', $type);
        }

        if ($structure)
        {
            $qb->andWhere('sf.structure = :structure')
                ->setParameter('structure', $structure);
        }
        else
        {
            $qb->andWhere('sf.structure IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $name
     * @param IStructure $structure
     * @return StructureFile|null
     */
    public function getOneByNameAndStructure(string $name, IStructure $structure): ?StructureFile
    {
        return $this->structureFileRepository->findOneBy(['structure' => $structure, 'name' => $name]);
    }

    /**
     * @param string $name
     * @param IStructure|null $structure
     * @param IStructureFile|null $structureFileIgnore
     * @return bool
     */
    public function isNameFree(string $name, IStructure $structure = null, IStructureFile $structureFileIgnore = null): bool
    {
        $qb = $this->structureFileRepository->createQueryBuilder('sf')
            ->select('sf')
            ->where('sf.name = :name')
            ->andWhere('sf.structure = :structure')
            ->setParameters([
                'name' => $name,
                'structure' => $structure
            ]);

        if ($structureFileIgnore)
        {
            $qb->andWhere('sf != :structureFileIgnore')
                ->setParameter('structureFileIgnore', $structureFileIgnore);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @param string $insertName
     * @param IFile $newFile
     * @param IStructure|null $structure
     * @return StructureFile
     */
    public function createNewStructureFile(string $insertName, IFile $newFile, IStructure $structure = null): IStructureFile
    {
        $newStructureFile = new StructureFile($insertName, $newFile, $structure);
        $this->entityManager->persist($newStructureFile);
        $this->entityManager->flush();

        return $newStructureFile;
    }

    /**
     * @param IStructureFile $structureFile
     * @throws \Exception
     * @return void
     */
    public function deleteStructureFile(IStructureFile $structureFile): void
    {
        $this->entityManager->remove($structureFile);
        $this->entityManager->flush();
    }
}