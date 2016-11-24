<?php

namespace Dravencms\File\Console;

use App\Model\Admin\Entities\Menu;
use App\Model\Admin\Repository\MenuRepository;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Kdyby\Doctrine\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

class InstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('dravencms:file:install')
            ->setDescription('Installs dravencms module');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var MenuRepository $adminMenuRepository */
        $adminMenuRepository = $this->getHelper('container')->getByType('App\Model\Admin\Repository\MenuRepository');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getHelper('container')->getByType('Kdyby\Doctrine\EntityManager');

        try {

            $aclResource = new AclResource('file', 'File');

            $entityManager->persist($aclResource);

            $aclOperationEdit = new AclOperation($aclResource, 'edit', 'Allows editation of file');
            $entityManager->persist($aclOperationEdit);
            $aclOperationDelete = new AclOperation($aclResource, 'delete', 'Allows deletion of file');
            $entityManager->persist($aclOperationDelete);

            $aclOperationDownloadEdit = new AclOperation($aclResource, 'downloadEdit', 'Allows editation of download');
            $entityManager->persist($aclOperationDownloadEdit);
            $aclOperationDownloadDelete = new AclOperation($aclResource, 'downloadDelete', 'Allows deletion of download');
            $entityManager->persist($aclOperationDownloadDelete);

            $adminMenu = new Menu('File manager', ':Admin:File:File', 'fa-archive', $aclOperationEdit);
            $entityManager->persist($adminMenu);

            $adminMenuDownload = new Menu('Download', ':Admin:File:Download', 'fa-download', $aclOperationDownloadEdit);

            $foundRoot = $adminMenuRepository->getOneByName('Site items');

            if ($foundRoot)
            {
                $adminMenuRepository->getMenuRepository()->persistAsLastChildOf($adminMenuDownload, $foundRoot);
            }
            else
            {
                $entityManager->persist($adminMenuDownload);
            }

            $entityManager->flush();

            $output->writeLn('Module installed successfully');
            return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeLn('<error>' . $e->getMessage() . '</error>');
            return 1; // non-zero return code means error
        }
    }
}