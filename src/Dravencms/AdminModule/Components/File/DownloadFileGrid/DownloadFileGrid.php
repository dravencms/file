<?php

/*
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace Dravencms\AdminModule\Components\File\DownloadFileGrid;

use Dravencms\Components\BaseGridFactory;
use Dravencms\Model\File\Entities\Download;
use Dravencms\Model\File\Repository\DownloadFileRepository;
use App\Model\Locale\Repository\LocaleRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;

/**
 * Description of DownloadFileGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadFileGrid extends Control
{
    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var DownloadFileRepository */
    private $downloadFileRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var LocaleRepository */
    private $localeRepository;

    /** @var Download */
    private $download;

    /**
     * @var array
     */
    public $onDelete = [];

    /**
     * DownloadFileGrid constructor.
     * @param Download $download
     * @param DownloadFileRepository $downloadFileRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     * @param LocaleRepository $localeRepository
     */
    public function __construct(Download $download, DownloadFileRepository $downloadFileRepository, BaseGridFactory $baseGridFactory, EntityManager $entityManager, LocaleRepository $localeRepository)
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->downloadFileRepository = $downloadFileRepository;
        $this->entityManager = $entityManager;
        $this->localeRepository = $localeRepository;
        $this->download = $download;
    }


    /**
     * @param $name
     * @return \Dravencms\Components\BaseGrid
     */
    public function createComponentGrid($name)
    {
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setModel($this->downloadFileRepository->getDownloadFileQueryBuilder($this->download));

        $grid->setDefaultSort(['position' => 'ASC']);
        $grid->addColumnText('name', 'Name')
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('structureFile.name', 'File')
            ->setCustomRender(function($row){
                return $row->getStructureFile()->getBasename();
            })
            ->setFilterText()
            ->setSuggestion();

        $grid->getColumn('name')->cellPrototype->class[] = 'center';

        $grid->addColumnNumber('downloadCount', 'Download count')
            ->setFilterNumber();

        $grid->getColumn('downloadCount')->cellPrototype->class[] = 'center';


        $grid->addColumnDate('updatedAt', 'Last edit', $this->localeRepository->getLocalizedDateTimeFormat())
            ->setSortable()
            ->setFilterDate();
        $grid->getColumn('updatedAt')->cellPrototype->class[] = 'center';

        $grid->addColumnNumber('position', 'Position')
            ->setFilterNumber()
            ->setSuggestion();

        $grid->getColumn('position')->cellPrototype->class[] = 'center';

        if ($this->presenter->isAllowed('file', 'downloadEdit')) {
            $grid->addActionHref('editFile', 'Upravit')
                ->setCustomHref(function($row){
                    return $this->presenter->link('editFile', ['downloadId' => $row->getDownload()->getId(), 'fileId' => $row->getId()]);
                })
                ->setIcon('pencil');
        }

        if ($this->presenter->isAllowed('file', 'downloadDelete')) {
            $grid->addActionHref('delete', 'Smazat', 'delete!')
                ->setCustomHref(function($row){
                    return $this->link('delete!', $row->getId());
                })
                ->setIcon('trash-o')
                ->setConfirm(function ($row) {
                    return ['Opravdu chcete smazat download file %s ?', $row->name];
                });


            $operations = ['delete' => 'Smazat'];
            $grid->setOperation($operations, [$this, 'gridOperationsHandler'])
                ->setConfirm('delete', 'Opravu chcete smazat %i download files ?');
        }
        $grid->setExport();

        return $grid;
    }

    /**
     * @param $action
     * @param $ids
     */
    public function gridOperationsHandler($action, $ids)
    {
        switch ($action)
        {
            case 'delete':
                $this->handleDelete($ids);
                break;
        }
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function handleDelete($id)
    {
        $downloadFiles = $this->downloadFileRepository->getById($id);
        foreach ($downloadFiles AS $downloadFile)
        {
            $this->entityManager->remove($downloadFile);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/DownloadFileGrid.latte');
        $template->render();
    }
}
