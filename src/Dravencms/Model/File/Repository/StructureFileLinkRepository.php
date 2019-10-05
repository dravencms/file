<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Model\File\Entities\StructureFileLink;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IStructure;
use Salamek\Files\Models\IStructureFile;
use Salamek\Files\Models\IStructureFileRepository;

class StructureFileLinkRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $structureFileLinkRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * StructureFileLinkRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->structureFileLinkRepository = $entityManager->getRepository(StructureFileLink::class);
    }

    /**
     * @param $id
     * @return mixed|null|StructureFileLink
     */
    public function getOneById($id)
    {
        return $this->structureFileLinkRepository->find($id);
    }

    /**
     * @param $id
     * @return StructureFileLink[]
     */
    public function getById($id)
    {
        return $this->structureFileLinkRepository->findBy(['id' => $id]);
    }

    /**
     * @return StructureFileLink[]
     */
    public function getAll()
    {
        return $this->structureFileLinkRepository->findAll();
    }
}