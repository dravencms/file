<?php declare(strict_types = 1);

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Dravencms\AdminModule\FileModule;

use Dravencms\AdminModule\Components\File\StructureFileForm\StructureFileForm;
use Dravencms\AdminModule\Components\File\StructureFileForm\StructureFileFormFactory;
use Dravencms\AdminModule\Components\File\StructureForm\StructureForm;
use Dravencms\AdminModule\Components\File\StructureForm\StructureFormFactory;
use Dravencms\AdminModule\Components\File\UploadFileForm\UploadFileForm;
use Dravencms\AdminModule\Components\File\UploadFileForm\UploadFileFormFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Flash;
use Dravencms\Model\File\Entities\Structure;
use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Dravencms\Model\File\Repository\StructureRepository;
use Dravencms\Database\EntityManager;
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

    public function startup(): void
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
     * @param null|int $structureId
     * @throws \Exception
     */
    public function actionDefault(int $structureId = null): void
    {
        $this->template->h1 = 'File manager';
        if ($structureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($structureId);
        }

        $this->template->parentStructure = $this->parentStructure;
        $this->template->directories = $this->structureRepository->getByParent($this->parentStructure);
        $this->template->structureFiles = $this->structureFileRepository->getByStructure($this->parentStructure);

        if ($this->parentStructure) {
            $this->template->structureInfo = $this->structureRepository->buildParentTree($this->parentStructure);
        } else {
            $this->template->structureInfo = [];
        }
    }

    /**
     * @param int|null $structureId
     * @param int|null $type
     */
    public function renderAjaxFileManager(int $structureId = null, int $type = null): void
    {
        $this->template->h1 = 'File manager selector';
        if ($structureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($structureId);
        }

        $this->template->parentStructure = $this->parentStructure;
        $this->template->directories = $this->structureRepository->getByParent($this->parentStructure);
        $this->template->structureFiles = $this->structureFileRepository->getByStructureAndType($this->parentStructure, $type);

        if ($this->parentStructure) {
            $this->template->structureInfo = $this->structureRepository->buildParentTree($this->parentStructure);
        } else {
            $this->template->structureInfo = [];
        }
    }

    /**
     * @param int|null $structureId
     * @param string|null $type
     */
    public function renderAjaxFileManagerSelector(int $structureId = null, string $type = null): void
    {
        $this->renderAjaxFileManager($structureId, $type);
    }

    /**
     * @param int $structureId
     * @throws \Exception
     */
    public function renderAjaxStructureInfo(int $structureId): void
    {
        $structure = $this->structureRepository->getOneById($structureId);
        $this->template->structure = $structure;
        $this->template->info = $this->fileStorage->getStructureFilesInfo($structure);
    }

    /**
     * @param int|null $filesStructureFilesId
     */
    public function renderAjaxFileInfo(int $filesStructureFilesId = null): void
    {
        $this->template->structureFile = $this->structureFileRepository->getOneById($filesStructureFilesId);
    }

    /**
     * @param int|null $filesStructureFilesId
     */
    public function actionAjaxStructureFileForm(int $filesStructureFilesId = null)
    {
        $this->structureFileEdit = $this->structureFileRepository->getOneById($filesStructureFilesId);
    }

    /**
     * @param null|integer $structureId
     * @param null|integer $parentStructureId
     */
    public function actionAjaxStructureForm(int $structureId = null, int $parentStructureId = null): void
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
     * @param int|null $filesStructureFilesId
     */
    public function renderAjaxFileUploadUpdate(int $filesStructureFilesId = null): void
    {
        $this->template->structureFile = $this->structureFileRepository->getOneById($filesStructureFilesId);
    }

    /**
     * @param int $filesStructureFilesId
     * @throws \Nette\Application\AbortException
     * @throws \Nette\Application\BadRequestException
     */
    public function handleFileDownload(int $filesStructureFilesId): void
    {
        $structureFile = $this->structureFileRepository->getOneById($filesStructureFilesId);
        if (!$structureFile) {
            $this->error();
        }

        $response = $this->fileStorage->downloadFile($structureFile);
        $this->sendResponse($response);
    }

    /**
     * @param int $structureId
     * @throws \Nette\Application\AbortException
     * @throws \Nette\Application\BadRequestException
     */
    public function handleStructureDownload(int $structureId): void
    {
        $structure = $this->structureRepository->getOneById($structureId);
        if (!$structure) {
            $this->error();
        }

        $response = $this->fileStorage->downloadStructure($structure);
        $this->sendResponse($response);
    }

    /**
     * @param int $filesStructureFilesId
     * @throws \Nette\Application\AbortException
     */
    public function handleFileDelete(int $filesStructureFilesId): void
    {
        $files = $this->structureFileRepository->getById($filesStructureFilesId);
        $filesStructureId = null;
        foreach ($files AS $file)
        {
            $filesStructureId = ($file->getStructure()? $file->getStructure()->getId() : null);
            $this->fileStorage->deleteStructureFile($file);
        }
        $this->flashMessage('File has been deleted', Flash::SUCCESS);

        $this->redirect('File:', $filesStructureId);
    }

    /**
     * @param int $structureId
     * @throws \Nette\Application\AbortException
     */
    public function handleStructureDelete(int $structureId): void
    {
        $structures = $this->structureRepository->getById($structureId);
        $structureParentId = null;
        foreach ($structures AS $structure)
        {
            $structureParentId = ($structure->getParent() ? $structure->getParent()->getId() : null);
            $this->fileStorage->deleteStructure($structure);
        }
        
        $this->flashMessage('Folder has been deleted', Flash::SUCCESS);

        $this->redirect('File:', $structureParentId);
    }

    /**
     * @return StructureForm
     */
    public function createComponentFormStructure(): StructureForm
    {
        $control = $this->structureFormFactory->create($this->parentStructure, $this->structureEdit);
        $control->onSuccess[] = function($structure)
        {
            $this->flashMessage('Directory has been saved', Flash::SUCCESS);
            $this->redirect('File:', ($structure->getParent() ? $structure->getParent()->getId() : null));
        };
        return $control;
    }

    /**
     * @return StructureFileForm
     */
    public function createComponentFormStructureFile(): StructureFileForm
    {
        $control = $this->structureFileFormFactory->create($this->structureFileEdit);
        $control->onSuccess[] = function($structureFile)
        {
            $this->flashMessage('File has been saved', Flash::SUCCESS);
            $this->redirect('File:', ($structureFile->getStructure() ? $structureFile->getStructure()->getId() : null));

        };
        return $control;
    }

    /**
     * @return UploadFileForm
     */
    public function createComponentFormUpload(): UploadFileForm
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
