<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace App\Model\File\Repository;

use App\Model\File\Entities\Structure;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Files\Models\IStructure;
use Salamek\Files\Models\IStructureRepository;

class StructureRepository implements IStructureRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $structureRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * MenuRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->structureRepository = $entityManager->getRepository(Structure::class);
    }

    /**
     * @param $id
     * @return mixed|null|Structure
     */
    public function getOneById($id)
    {
        return $this->structureRepository->find($id);
    }

    /**
     * @param $id
     * @return Structure[]
     */
    public function getById($id)
    {
        return $this->structureRepository->findBy(['id' => $id]);
    }

    /**
     * @param IStructure|null $structure
     * @return Structure[]
     */
    public function getByParent(IStructure $structure = null)
    {
        return $this->structureRepository->findBy(['parent' => $structure]);
    }

    /**
     * @return Structure[]
     */
    public function getAll()
    {
        return $this->structureRepository->findAll();
    }

    /**
     * @param IStructure $structure
     * @return IStructure[]
     */
    private function buildParentTreeResolver(IStructure $structure)
    {
        $breadcrumb = [];

        $breadcrumb[] = $structure;

        if ($structure->getParent()) {
            foreach ($this->buildParentTreeResolver($structure->getParent()) AS $sub) {
                $breadcrumb[] = $sub;
            }
        }
        return $breadcrumb;
    }

    /**
     * @param IStructure $structure
     * @return IStructure[]
     */
    public function buildParentTree(IStructure $structure)
    {
        return array_reverse($this->buildParentTreeResolver($structure));
    }

    /**
     * @param $options
     * @return mixed
     */
    public function getTree($options = [])
    {
        return $this->structureRepository->childrenHierarchy(null, false, $options);
    }
    
    /**
     * @param $name
     * @param IStructure|null $parentStructure
     * @param IStructure|null $ignoreStructure
     * @return boolean
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, IStructure $parentStructure = null, IStructure $ignoreStructure = null)
    {
        $qb = $this->structureRepository->createQueryBuilder('s')
            ->select('s')
            ->where('s.name = :name')
            ->andWhere('s.parent = :parent')
            ->setParameters([
                'name' => $name,
                'parent' => $parentStructure
            ]);

        if ($ignoreStructure)
        {
            $qb->andWhere('s != :ignoreStructure')
                ->setParameter('ignoreStructure', $ignoreStructure);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @param IStructure $child
     * @param IStructure $root
     */
    public function persistAsLastChildOf(IStructure $child, IStructure $root)
    {
        $this->structureRepository->persistAsLastChildOf($child, $root);
    }

    /**
     * @param IStructure $structure
     * @throws \Exception
     * @return void
     */
    public function deleteStructure(IStructure $structure)
    {
        $this->entityManager->remove($structure);
        $this->entityManager->flush();
    }
}