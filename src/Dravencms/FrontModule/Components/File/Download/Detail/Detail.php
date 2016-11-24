<?php

namespace Dravencms\FrontModule\Components\File\Download\Detail;

use Dravencms\Components\BaseControl;
use Dravencms\Model\File\Repository\DownloadFileRepository;
use Dravencms\Model\File\Repository\DownloadRepository;
use IPub\VisualPaginator\Components\Control;
use Salamek\Cms\ICmsActionOption;
use Salamek\Files\FileStorage;

class Detail extends BaseControl
{
    /** @var ICmsActionOption */
    private $cmsActionOption;

    /** @var DownloadRepository */
    private $downloadRepository;

    /** @var DownloadFileRepository */
    private $downloadFileRepository;

    /** @var FileStorage */
    private $fileStorage;

    public function __construct(ICmsActionOption $cmsActionOption, DownloadRepository $downloadRepository, DownloadFileRepository $downloadFileRepository, FileStorage $fileStorage)
    {
        parent::__construct();
        $this->cmsActionOption = $cmsActionOption;
        $this->downloadRepository = $downloadRepository;
        $this->downloadFileRepository = $downloadFileRepository;
        $this->fileStorage = $fileStorage;
    }


    public function render()
    {
        $template = $this->template;
        $download = $this->downloadRepository->getOneById($this->cmsActionOption->getParameter('id'));


        $visualPaginator = $this['visualPaginator'];

        $paginator = $visualPaginator->getPaginator();
        $paginator->itemsPerPage = 10;
        $paginator->itemCount = $download->getDownloadFiles()->count();

        $template->download = $download;
        $template->files = $this->downloadFileRepository->getByDownload($download, $paginator->itemsPerPage, $paginator->offset);

        $template->setFile(__DIR__.'/detail.latte');
        $template->render();
    }

    /**
     * @param $downloadFileId
     */
    public function handleDownload($downloadFileId)
    {
        $downloadFile = $this->downloadFileRepository->getOneById($downloadFileId);
        if (!$downloadFile)
        {
            $this->presenter->flashMessage('File not found!', 'alert-danger');
        }
        else
        {
            $this->downloadFileRepository->logDownload($downloadFile);
            $response = $this->fileStorage->downloadFile($downloadFile->getStructureFile());
            $this->presenter->sendResponse($response);
        }
    }

    /**
     * @return Control
     */
    protected function createComponentVisualPaginator()
    {
        // Init visual paginator
        $control = new Control();
        $control->setTemplateFile('bootstrap.latte');

        $control->onShowPage[] = (function ($component, $page) {
            if ($this->presenter->isAjax()){
                $this->redrawControl('detail');
            }
        });

        return $control;
    }
}
