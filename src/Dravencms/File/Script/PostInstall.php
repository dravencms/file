<?php

namespace Dravencms\File\Script;

use Dravencms\Model\Admin\Entities\Menu;
use Dravencms\Model\Admin\Repository\MenuRepository;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Dravencms\Model\User\Repository\AclResourceRepository;
use Dravencms\Packager\IPackage;
use Dravencms\Packager\IScript;
use Kdyby\Doctrine\EntityManager;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
class PostInstall implements IScript
{
    /** @var MenuRepository  */
    private $menuRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclOperationRepository */
    private $aclOperationRepository;

    /** @var AclResourceRepository */
    private $aclResourceRepository;

    /**
     * PostInstall constructor.
     * @param MenuRepository $menuRepository
     * @param EntityManager $entityManager
     * @param AclOperationRepository $aclOperationRepository
     * @param AclResourceRepository $aclResourceRepository
     */
    public function __construct(MenuRepository $menuRepository, EntityManager $entityManager, AclOperationRepository $aclOperationRepository, AclResourceRepository $aclResourceRepository)
    {
        $this->menuRepository = $menuRepository;
        $this->entityManager = $entityManager;
        $this->aclOperationRepository = $aclOperationRepository;
        $this->aclResourceRepository = $aclResourceRepository;
    }

    /**
     * @param IPackage $package
     * @throws \Exception
     */
    public function run(IPackage $package)
    {
        if (!$aclResource = $this->aclResourceRepository->getOneByName('file')) {
            $aclResource = new AclResource('file', 'File');

            $this->entityManager->persist($aclResource);
        }

        if (!$aclOperationEdit = $this->aclOperationRepository->getOneByName('edit')) {
            $aclOperationEdit = new AclOperation($aclResource, 'edit', 'Allows editation of file');
            $this->entityManager->persist($aclOperationEdit);
        }

        if (!$aclOperationDelete = $this->aclOperationRepository->getOneByName('delete')) {
            $aclOperationDelete = new AclOperation($aclResource, 'delete', 'Allows deletion of file');
            $this->entityManager->persist($aclOperationDelete);
        }

        if (!$aclOperationDownloadEdit = $this->aclOperationRepository->getOneByName('downloadEdit')) {
            $aclOperationDownloadEdit = new AclOperation($aclResource, 'downloadEdit', 'Allows editation of download');
            $this->entityManager->persist($aclOperationDownloadEdit);
        }

        if (!$aclOperationDownloadDelete = $this->aclOperationRepository->getOneByName('downloadDelete')) {
            $aclOperationDownloadDelete = new AclOperation($aclResource, 'downloadDelete', 'Allows deletion of download');
            $this->entityManager->persist($aclOperationDownloadDelete);
        }

        if (!$this->menuRepository->getOneByPresenter(':Admin:File:File')) {
            $adminMenu = new Menu('File manager', ':Admin:File:File', 'fa-archive', $aclOperationEdit);
            $this->entityManager->persist($adminMenu);
        }

        if (!$this->menuRepository->getOneByPresenter(':Admin:File:Download')) {
            $adminMenuDownload = new Menu('Download', ':Admin:File:Download', 'fa-download', $aclOperationDownloadEdit);

            $foundRoot = $this->menuRepository->getOneByName('Site items');

            if ($foundRoot) {
                $this->menuRepository->getMenuRepository()->persistAsLastChildOf($adminMenuDownload, $foundRoot);
            } else {
                $this->entityManager->persist($adminMenuDownload);
            }
        }

        $this->entityManager->flush();

    }
}