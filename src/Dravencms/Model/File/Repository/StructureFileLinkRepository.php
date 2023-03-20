<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Model\File\Entities\StructureFileLink;
use Dravencms\Database\EntityManager;

class StructureFileLinkRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|StructureFileLink */
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
     * @param int $id
     * @return StructureFileLink|null
     */
    public function getOneById(int $id): ?StructureFileLink
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
    
    /**
     * @param array $parameters
     * @return StructureFileLink
     */
    public function getOneByParameters(array $parameters): ?StructureFileLink
    {
        return $this->structureFileLinkRepository->findOneBy($parameters);
    }
}
