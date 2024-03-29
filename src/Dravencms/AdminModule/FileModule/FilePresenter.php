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


    private $iconFolder = 'folder';
    private $iconBack = 'folder_back';


    /** @var SessionSection */
    private $fileSession;

    public function startup(): void
    {
        parent::startup();

        $this->template->iconFolder = $this->iconFolder;
        $this->template->iconBack = $this->iconBack;

        $this->fileSession = $this->getSession('file');
    }

    /**
     * @param null|int $structureId
     * @throws \Exception
     */
    public function actionDefault(int $structureId = null): void
    {
        $this->template->h1 = $this->translator->translate('file.fileManager');
        if ($structureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($structureId);
        }

        $this->template->fileStorage = $this->fileStorage;
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
     * @param string|null $type
     */
    public function renderAjaxFileManager(int $structureId = null, string $type = null): void
    {
        $this->template->h1 = $this->translator->translate('file.fileManagerSelector');
        if ($structureId)
        {
            $this->parentStructure = $this->structureRepository->getOneById($structureId);
        }

        $this->template->fileStorage = $this->fileStorage;
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
        $structureFile = $this->structureFileRepository->getOneById($filesStructureFilesId);
        $this->template->structureFile = $structureFile;
        $this->template->filePath = $this->fileStorage->getFileSystemPath($structureFile->getFile());
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
    public function handleFileDelete(int $filesStructureFileId): void
    {
        $structureFile = $this->structureFileRepository->getOneById($filesStructureFileId);
        $filesStructureId = ($structureFile->getStructure() ? $structureFile->getStructure()->getId() : null);
        $this->deleteFile($structureFile);
       
        $this->flashMessage($this->translator->translate('file.fileHasBeenSucessfullyDeleted'), Flash::SUCCESS);

        $this->redirect('File:', $filesStructureId);
    }

    private function deleteFile(StructureFile $structureFile): bool {
        $filesToCheckIds = [];
        $hasUndeletable = false;

        $allAgree = [true];
        foreach ($structureFile->getStructureFileLinks() AS $structureFileLink) {
            if ($structureFileLink->isUsed() || !$structureFileLink->isAutoclean()) {
                $allAgree[] = false;
            } else {
                $allAgree[] = true;
                $this->entityManager->remove($structureFileLink);
            }
        }

        $canDelete = (count(array_unique($allAgree)) === 1 && end($allAgree) === true);

        if ($canDelete) {
            $filesToCheckIds[] = $structureFile->getFile()->getId();
            $this->entityManager->remove($structureFile);
        } else {
            $hasUndeletable = true;
        }

        $this->entityManager->flush();

        foreach($filesToCheckIds AS $filesToCheckId) {
            $fileFile = $this->fileRepository->getOneById($filesToCheckId);
            if (!$fileFile->getStructureFiles()->count()) {
                $this->entityManager->remove($fileFile);
            }
        }

        $this->entityManager->flush();
        
        return $hasUndeletable;
    }
    
    private function resolveStructureChildren(Structure $structure): array {
        $allChildren = $children = $structure->getChildren()->toArray();
        if (count($children) > 0) {
            foreach ($children AS $child) {
                $allChildren = array_merge($allChildren, $this->resolveStructureChildren($child));
            }
        }
        
        return $allChildren;
    }
    
    
    /**
     * @param int $structureId
     * @throws \Nette\Application\AbortException
     */
    public function handleStructureDelete(int $structureId): void
    {
        $structure = $this->structureRepository->getOneById($structureId);
  
        $structureParentId = ($structure->getParent() ? $structure->getParent()->getId() : null);
        
        $children = array_reverse($this->resolveStructureChildren($structure)) + [$structure];
        $hasUndeletableChildren = false;
        foreach ($children AS $child) {
            $hasUndeletable = false;
            $structureFiles = $child->getStructureFiles();
            foreach($structureFiles AS $structureFile) {
                if ($this->deleteFile($structureFile)) {
                    $hasUndeletable = true;
                }
            }
            
            if (!$hasUndeletable) {
                $this->fileStorage->deleteStructure($child);
            } else {
                $hasUndeletableChildren = true;
            }
        }

        if (!$hasUndeletableChildren) {
            $this->fileStorage->deleteStructure($structure);
            $this->flashMessage($this->translator->translate('file.folderHasBeenSucessfullyDeleted'), Flash::SUCCESS);
        } else {
            $this->flashMessage($this->translator->translate('file.folderWasNotDeletedSomeFilesInsideAreUsedByOtherSystemComponents'), Flash::WARNING);
        }
        
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
            $this->flashMessage($this->translator->translate('file.folderHasBeenSaved'), Flash::SUCCESS);
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
            $this->flashMessage($this->translator->translate('file.fileHasBeenSaved'), Flash::SUCCESS);
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
