<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\FileModule;


use Dravencms\AdminModule\Components\File\DownloadFileFormFactory;
use Dravencms\AdminModule\Components\File\DownloadFileGridFactory;
use Dravencms\AdminModule\Components\File\DownloadFormFactory;
use Dravencms\AdminModule\Components\File\DownloadGridFactory;
use Dravencms\AdminModule\SecuredPresenter;
use App\Model\File\Entities\Download;
use App\Model\File\Entities\DownloadFile;
use App\Model\File\Repository\DownloadFileRepository;
use App\Model\File\Repository\DownloadRepository;

class DownloadPresenter extends SecuredPresenter
{
    /** @var DownloadRepository @inject */
    public $downloadRepository;

    /** @var DownloadFileRepository @inject */
    public $fileRepository;

    /** @var DownloadFormFactory @inject */
    public $downloadFormFactory;

    /** @var DownloadGridFactory @inject */
    public $downloadGridFactory;

    /** @var DownloadFileFormFactory @inject */
    public $fileFormFactory;

    /** @var DownloadFileGridFactory @inject */
    public $fileGridFactory;


    /** @var null|Download */
    private $download = null;

    /** @var null|DownloadFile */
    private $file = null;

    public function renderDefault()
    {
        $this->template->h1 = 'Downloads';
    }

    public function actionEdit($id)
    {
        if ($id) {
            $this->template->h1 = 'Edit download';
            $carousel = $this->downloadRepository->getOneById($id);
            if (!$carousel) {
                $this->error();
            }

            $this->download = $carousel;
        } else {
            $this->template->h1 = 'New download';
        }
    }

    /**
     * @param $id
     */
    public function actionFiles($id)
    {
        $this->download = $this->downloadRepository->getOneById($id);
        $this->template->download = $this->download;
        $this->template->h1 = 'Download files';
    }

    /**
     * @param $downloadId
     * @param null $fileId
     * @throws \Nette\Application\BadRequestException
     */
    public function actionEditFile($downloadId, $fileId = null)
    {
        $this->download = $this->downloadRepository->getOneById($downloadId);
        if ($fileId)
        {
            $file = $this->fileRepository->getOneById($fileId);
            if (!$file) {
                $this->error();
            }

            $this->file = $file;
            $this->template->h1 = 'Edit download file';
        }
        else
        {
            $this->template->h1 = 'New download file';
        }
    }


    public function createComponentFormDownload()
    {
        $control = $this->downloadFormFactory->create($this->download);
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Download has been successfully saved', 'alert-success');
            $this->redirect('Download:');
        };
        return $control;
    }

    public function createComponentGridDownload()
    {
        $control = $this->downloadGridFactory->create();
        $control->onDelete[] = function()
        {
            $this->flashMessage('Download has been successfully deleted', 'alert-success');
            $this->redirect('Download:');
        };
        return $control;
    }

    public function createComponentFormFile()
    {
        $control = $this->fileFormFactory->create($this->download, $this->file);
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Download item has been successfully saved', 'alert-success');
            $this->redirect('Download:files', $this->download->getId());
        };
        return $control;
    }

    public function createComponentGridFile()
    {
        $control = $this->fileGridFactory->create($this->download);
        $control->onDelete[] = function()
        {
            $this->flashMessage('Download item has been successfully deleted', 'alert-success');
            $this->redirect('Download:files', $this->download->getId());
        };
        return $control;
    }
}