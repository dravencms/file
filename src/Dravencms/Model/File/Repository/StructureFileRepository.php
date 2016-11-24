<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Model\File\Entities\StructureFile;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IStructure;
use Salamek\Files\Models\IStructureFile;
use Salamek\Files\Models\IStructureFileRepository;

class StructureFileRepository implements IStructureFileRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
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
    public function getOneById($id)
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
    public function getByStructureAndType(IStructure $structure = null, $type = null)
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
     * @param $name
     * @param IStructure $structure
     * @return StructureFile[]
     */
    public function getOneByNameAndStructure($name, IStructure $structure)
    {
        return $this->structureFileRepository->findOneBy(['structure' => $structure, 'name' => $name]);
    }

    /**
     * @param $name
     * @param IStructure|null $structure
     * @param IStructureFile|null $structureFileIgnore
     * @return boolean
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, IStructure $structure = null, IStructureFile $structureFileIgnore = null)
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
     * @param $insertName
     * @param IFile $newFile
     * @param IStructure|null $structure
     * @return StructureFile
     * @throws \Exception
     */
    public function createNewStructureFile($insertName, IFile $newFile, IStructure $structure = null)
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
    public function deleteStructureFile(IStructureFile $structureFile)
    {
        $this->entityManager->remove($structureFile);
        $this->entityManager->flush();
    }
}