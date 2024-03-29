<?php declare(strict_types = 1);
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

namespace Dravencms\AdminModule\Components\File\StructureFileForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Components\BaseForm\Form;
use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;
use Salamek\Files\FileStorage;

/**
 * Description of FileForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class StructureFileForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var User */
    private $user;

    /** @var StructureFileRepository */
    private $structureFileRepository;

    /** @var FileStorage */
    private $fileStorage;

    /** @var StructureFile */
    private $structureFile;

    /** @var array */
    public $onSuccess = [];

    /**
     * StructureFileForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param User $user
     * @param FileStorage $fileStorage
     * @param StructureFileRepository $structureFileRepository
     * @param StructureFile $structureFile
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        User $user,
        FileStorage $fileStorage,
        StructureFileRepository $structureFileRepository,
        StructureFile $structureFile
    ) {

        $this->structureFile = $structureFile;
        $this->fileStorage = $fileStorage;
        $this->user = $user;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->structureFileRepository = $structureFileRepository;

        $this['form']->setDefaults([
            'name' => $this->structureFile->getName()
        ]);
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name')
            ->setRequired('file.pleaseEnterName')
            ->addRule(Form::MAX_LENGTH, 'nameIsTooLong', 255);

        $form->addUpload('file');

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();
        if (!$this->structureFileRepository->isNameFree($values->name, $this->structureFile->getStructure(), $this->structureFile)) {
            $form->addError('file.thisNameIsAlreadyUsed');
        }

        if (!$this->user->isAllowed('file', 'edit')) {
            $form->addError('file.youHaveNoPermissionToEditThisFile');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        $structureFile = $this->structureFile;
        $structureFile->setName($values->name);

        if ($values->file->isOk()) {
            $file = $this->fileStorage->saveFile($values->file);
            //$deleteFile = $structureFile->getFile();
            $structureFile->setFile($file);
            //$this->fileStorage->deleteFile($deleteFile);
        }

        $this->entityManager->persist($structureFile);

        $this->entityManager->flush();

        $this->onSuccess($structureFile);
    }

    public function render(): void
    {
        $template = $this->template;
        $template->structureFile = $this->structureFile;
        $template->setFile(__DIR__ . '/StructureFileForm.latte');
        $template->render();
    }
}
