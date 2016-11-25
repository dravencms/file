<?php

namespace Dravencms\Form\Script;

use Dravencms\Model\Admin\Entities\Menu;
use Dravencms\Model\Admin\Repository\MenuRepository;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Packager\IPackage;
use Dravencms\Packager\IScript;
use Kdyby\Doctrine\EntityManager;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
class PostInstall implements IScript
{
    private $menuRepository;
    private $entityManager;

    public function __construct(MenuRepository $menuRepository, EntityManager $entityManager)
    {
        $this->menuRepository = $menuRepository;
        $this->entityManager = $entityManager;
    }

    public function run(IPackage $package)
    {
        $aclResource = new AclResource('file', 'File');

        $this->entityManager->persist($aclResource);

        $aclOperationEdit = new AclOperation($aclResource, 'edit', 'Allows editation of file');
        $this->entityManager->persist($aclOperationEdit);
        $aclOperationDelete = new AclOperation($aclResource, 'delete', 'Allows deletion of file');
        $this->entityManager->persist($aclOperationDelete);

        $aclOperationDownloadEdit = new AclOperation($aclResource, 'downloadEdit', 'Allows editation of download');
        $this->entityManager->persist($aclOperationDownloadEdit);
        $aclOperationDownloadDelete = new AclOperation($aclResource, 'downloadDelete', 'Allows deletion of download');
        $this->entityManager->persist($aclOperationDownloadDelete);

        $adminMenu = new Menu('File manager', ':Admin:File:File', 'fa-archive', $aclOperationEdit);
        $this->entityManager->persist($adminMenu);

        $adminMenuDownload = new Menu('Download', ':Admin:File:Download', 'fa-download', $aclOperationDownloadEdit);

        $foundRoot = $this->menuRepository->getOneByName('Site items');

        if ($foundRoot)
        {
            $this->menuRepository->getMenuRepository()->persistAsLastChildOf($adminMenuDownload, $foundRoot);
        }
        else
        {
            $this->entityManager->persist($adminMenuDownload);
        }

        $this->entityManager->flush();

    }
}