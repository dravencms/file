<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace App\Model\File\Repository;

use App\Model\File\Entities\Download;
use App\Model\File\Entities\DownloadFile;
use Gedmo\Translatable\TranslatableListener;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Cms\Models\ILocale;

class DownloadFileRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $downloadFileRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * DownloadRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->downloadFileRepository = $entityManager->getRepository(DownloadFile::class);
    }

    /**
     * @param $id
     * @return mixed|null|DownloadFile
     */
    public function getOneById($id)
    {
        return $this->downloadFileRepository->find($id);
    }

    /**
     * @param $id
     * @return DownloadFile[]
     */
    public function getById($id)
    {
        return $this->downloadFileRepository->findBy(['id' => $id]);
    }

    /**
     * @param Download $download
     * @param null $limit
     * @param null $offset
     * @return array
     */
    public function getByDownload(Download $download, $limit = null, $offset = null)
    {
        return $this->downloadFileRepository->findBy(['download' => $download],[], $limit, $offset);
    }

    /**
     * @param Download $download
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getDownloadFileQueryBuilder(Download $download)
    {
        $qb = $this->downloadFileRepository->createQueryBuilder('df')
            ->select('df')
            ->where('df.download = :download')
            ->setParameter('download', $download);
        return $qb;
    }

    /**
     * @param $name
     * @param ILocale $locale
     * @param Download $download
     * @param DownloadFile|null $downloadFileIgnore
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, ILocale $locale, Download $download, DownloadFile $downloadFileIgnore = null)
    {
        $qb = $this->downloadFileRepository->createQueryBuilder('df')
            ->select('df')
            ->where('df.name = :name')
            ->andWhere('df.download = :download')
            ->setParameters([
                'name' => $name,
                'download' => $download
            ]);

        if ($downloadFileIgnore)
        {
            $qb->andWhere('df != :downloadFileIgnore')
                ->setParameter('downloadFileIgnore', $downloadFileIgnore);
        }

        $query = $qb->getQuery();
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale->getLanguageCode());

        return (is_null($query->getOneOrNullResult()));
    }

    /**
     * @param DownloadFile $downloadFile
     * @throws \Exception
     */
    public function logDownload(DownloadFile $downloadFile)
    {
        $downloadFile->setDownloadCount($downloadFile->getDownloadCount() + 1);
        $this->entityManager->persist($downloadFile);
        $this->entityManager->flush();
    }
}