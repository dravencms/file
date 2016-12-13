<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Dravencms\AdminModule\FileModule;

use Dravencms\AdminModule\Components\File\StructureFileForm\StructureFileFormFactory;
use Dravencms\AdminModule\Components\File\StructureForm\StructureFormFactory;
use Dravencms\AdminModule\Components\File\UploadFileForm\UploadFileFormFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\File\Entities\Structure;
use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Dravencms\Model\File\Repository\StructureRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\SessionSection;
use Salamek\Files\FileStorage;

/**
 * Description of GalleryPresenter
 *
 * @author sadam
 */
class FilePresenter extends SecuredPresenter
{
    /** @var FileRepository @inject */
    public $fileRepository;

    /** @var StructureRepository @inject */
    public $structureRepository;

    /** @var StructureFileRepository @inject */
    public $structureFileRepository;

    /** @var StructureFormFactory @inject */
    public $structureFormFactory;

    /** @var StructureFileFormFactory @inject */
    public $structureFileFormFactory;

    /** @var UploadFileFormFactory @inject */
    public $uploadFileFormFactory;

    /** @var EntityManager @inject */
    public $entityManager;

    /** @var null|Structure */
    private $parentStructure = null;

    /** @var null|Structure */
    private $structureEdit = null;

    /** @var null|StructureFile */
    private $structureFileEdit = null;

    /** @var FileStorage @inject */
    public $fileStorage;


    private $iconDefault = 'default';
    private $iconFolder = 'folder';
    private $iconBack = 'folder_back';
    
    private $dataDir;

    /** @var SessionSection */
    private $fileSession;

    public function startup()
    {
        parent::startup();
        $this->dataDir = $this->fileStorage->getDataDir();

        $this->template->imagePath = $this->template->basePath.$this->fileStorage->getIconDirWww();
        $this->template->iconDefault = $this->iconDefault;
        $this->template->iconFolder = $this->iconFolder;
        $this->template->iconBack = $this->iconBack;
        $this->template->dataDir = $this->dataDir;

        $this->fileSession = $this->getSession('file');
    }

    /**
     * @param null $structureId
     * @throws \Exception
     */
    public function actionDefault($structureId = null)
    {
        $this->template->h1 = 'File manager';
        if ($structureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($structureId);
        }

        $this->template->parentStructure = $this->parentStructure;
        $this->template->directories = $this->structureRepository->getByParent($this->parentStructure);
        $this->template->files = $this->structureFileRepository->getByStructure($this->parentStructure);

        if ($this->parentStructure) {
            $this->template->structureInfo = $this->structureRepository->buildParentTree($this->parentStructure);
        } else {
            $this->template->structureInfo = [];
        }
    }

    /**
     * @param null $structureId
     * @param null $type
     * @throws \Exception
     */
    public function renderAjaxFileManager($structureId = null, $type = null)
    {
        $this->template->h1 = 'File manager selector';
        if ($structureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($structureId);
        }

        $this->template->parentStructure = $this->parentStructure;
        $this->template->directories = $this->structureRepository->getByParent($this->parentStructure);
        $this->template->files = $this->structureFileRepository->getByStructureAndType($this->parentStructure, $type);

        if ($this->parentStructure) {
            $this->template->structureInfo = $this->structureRepository->buildParentTree($this->parentStructure);
        } else {
            $this->template->structureInfo = [];
        }
    }

    /**
     * @param null $structureId
     * @param null $type
     */
    public function renderAjaxFileManagerSelector($structureId = null, $type = null)
    {
        $this->renderAjaxFileManager($structureId, $type);
    }

    /**
     * @param null $structureId
     */
    public function renderAjaxStructureInfo($structureId)
    {
        $structure = $this->structureRepository->getOneById($structureId);
        $this->template->structure = $structure;
        $this->template->info = $this->fileStorage->getStructureFilesInfo($structure);
    }

    /**
     * @param null $filesStructureFilesId
     */
    public function renderAjaxFileInfo($filesStructureFilesId = null)
    {
        $this->template->file = $this->structureFileRepository->getOneById($filesStructureFilesId);
    }

    /**
     * @param null $filesStructureFilesId
     */
    public function actionAjaxStructureFileForm($filesStructureFilesId = null)
    {
        $this->structureFileEdit = $this->structureFileRepository->getOneById($filesStructureFilesId);
    }

    /**
     * @param null|integer $structureId
     * @param null|integer $parentStructureId
     */
    public function actionAjaxStructureForm($structureId = null, $parentStructureId = null)
    {
        if ($parentStructureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($parentStructureId);
        }

        if ($structureId)
        {
            $this->structureEdit = $this->structureRepository->getOneById($structureId);
        }
    }

    /**
     * @param null $filesStructureFilesId
     */
    public function renderAjaxFileUploadUpdate($filesStructureFilesId = null)
    {
        $this->template->file = $this->structureFileRepository->getOneById($filesStructureFilesId);
    }

    /**
     * @param $filesStructureFilesId
     * @throws \Nette\Application\BadRequestException
     */
    public function handleFileDownload($filesStructureFilesId)
    {
        $structureFile = $this->structureFileRepository->getOneById($filesStructureFilesId);
        if (!$structureFile) {
            $this->error();
        }

        $response = $this->fileStorage->downloadFile($structureFile);
        $this->sendResponse($response);
    }

    /**
     * @param $structureId
     * @throws \Exception
     * @throws \Nette\Application\BadRequestException
     */
    public function handleStructureDownload($structureId)
    {
        $structure = $this->structureRepository->getOneById($structureId);
        if (!$structure) {
            $this->error();
        }

        $response = $this->fileStorage->downloadStructure($structure);
        $this->sendResponse($response);
    }

    /**
     * @param $filesStructureFilesId
     * @throws \Nette\Application\BadRequestException
     */
    public function handleFileDelete($filesStructureFilesId)
    {
        $files = $this->structureFileRepository->getById($filesStructureFilesId);
        $filesStructureId = null;
        foreach ($files AS $file)
        {
            $filesStructureId = ($file->getStructure()? $file->getStructure()->getId() : null);
            $this->fileStorage->deleteStructureFile($file);
        }
        $this->flashMessage('File has been deleted', 'alert-success');

        $this->redirect('File:', $filesStructureId);
    }

    /**
     * @param $structureId
     * @throws \Exception
     * @throws \Nette\Application\BadRequestException
     */
    public function handleStructureDelete($structureId)
    {
        $structures = $this->structureRepository->getById($structureId);
        $structureParentId = null;
        foreach ($structures AS $structure)
        {
            $structureParentId = ($structure->getParent() ? $structure->getParent()->getId() : null);
            $this->fileStorage->deleteStructure($structure);
        }
        
        $this->flashMessage('Folder has been deleted', 'alert-success');

        $this->redirect('File:', $structureParentId);
    }

    /**
     * @return \AdminModule\Components\File\RobotsForm
     */
    public function createComponentFormStructure()
    {
        $control = $this->structureFormFactory->create($this->parentStructure, $this->structureEdit);
        $control->onSuccess[] = function($structure)
        {
            $this->flashMessage('Directory has been saved', 'alert-success');
            $this->redirect('File:', ($structure->getParent() ? $structure->getParent()->getId() : null));
        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\File\StructureFileForm
     */
    public function createComponentFormStructureFile()
    {
        $control = $this->structureFileFormFactory->create($this->structureFileEdit);
        $control->onSuccess[] = function($structureFile)
        {
            $this->flashMessage('File has been saved', 'alert-success');
            $this->redirect('File:', ($structureFile->getStructure() ? $structureFile->getStructure()->getId() : null));

        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\File\UploadFileForm
     */
    public function createComponentFormUpload()
    {
        $control = $this->uploadFileFormFactory->create($this->parentStructure);
        $control->onSuccess[] = function($structureFile)
        {
            $this->payload->filesStructureFilesId = $structureFile->getId();
            $this->sendPayload();
        };
        return $control;
    }
}
