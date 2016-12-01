<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use Dravencms\Locale\TLocalizedRepository;
use Dravencms\Model\File\Entities\Download;
use Gedmo\Translatable\TranslatableListener;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Dravencms\Model\Locale\Entities\ILocale;

class DownloadRepository
{
    use TLocalizedRepository;
    
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $downloadRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * DownloadRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->downloadRepository = $entityManager->getRepository(Download::class);
    }

    /**
     * @param $id
     * @return mixed|null|Download
     */
    public function getOneById($id)
    {
        return $this->downloadRepository->find($id);
    }

    /**
     * @param $id
     * @return Download[]
     */
    public function getById($id)
    {
        return $this->downloadRepository->findBy(['id' => $id]);
    }

    /**
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    public function getDownloadQueryBuilder()
    {
        $qb = $this->downloadRepository->createQueryBuilder('d')
            ->select('d');
        return $qb;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->downloadRepository->findAll();
    }

    /**
     * @param $name
     * @param ILocale $locale
     * @param Download|null $downloadIgnore
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, ILocale $locale, Download $downloadIgnore = null)
    {
        $qb = $this->downloadRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.name = :name')
            ->setParameters([
                'name' => $name
            ]);

        if ($downloadIgnore)
        {
            $qb->andWhere('d != :downloadIgnore')
                ->setParameter('downloadIgnore', $downloadIgnore);
        }

        $query = $qb->getQuery();

        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale->getLanguageCode());

        return (is_null($query->getOneOrNullResult()));
    }
}