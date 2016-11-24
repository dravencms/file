<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Repository;

use App\Model\BaseRepository;
use Dravencms\Model\File\Entities\Download;
use Gedmo\Translatable\TranslatableListener;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Salamek\Cms\CmsActionOption;
use Salamek\Cms\ICmsActionOption;
use Salamek\Cms\ICmsComponentRepository;
use Salamek\Cms\Models\ILocale;

class DownloadRepository extends BaseRepository implements ICmsComponentRepository
{
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

    /**
     * @param string $componentAction
     * @return ICmsActionOption[]
     */
    public function getActionOptions($componentAction)
    {
        switch ($componentAction)
        {
            case 'Detail':
                $return = [];
                /** @var Download $download */
                foreach ($this->downloadRepository->findAll() AS $download) {
                    $return[] = new CmsActionOption($download->getName(), ['id' => $download->getId()]);
                }
                break;

            default:
                return false;
                break;
        }


        return $return;
    }

    /**
     * @param string $componentAction
     * @param array $parameters
     * @param ILocale $locale
     * @return null|CmsActionOption
     */
    public function getActionOption($componentAction, array $parameters, ILocale $locale)
    {
        $found = $this->findTranslatedOneBy($this->downloadRepository, $locale, $parameters);

        if ($found)
        {
            return new CmsActionOption($found->getName(), $parameters);
        }

        return null;
    }
}