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

namespace Dravencms\AdminModule\Components\File\UploadFileForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\File\Entities\Structure;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;
use Salamek\Files\FileStorage;
use Salamek\Files\Tools;

/**
 * Description of UploadFileForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class UploadFileForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var StructureFileRepository */
    private $structureFileRepository;

    /** @var FileStorage */
    private $fileStorage;

    /** @var Structure|null  */
    private $structureParent = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * UploadFileForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param StructureFileRepository $structureFileRepository
     * @param FileStorage $fileStorage
     * @param Structure|null $structureParent
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        StructureFileRepository $structureFileRepository,
        FileStorage $fileStorage,
        Structure $structureParent = null
    ) {
        parent::__construct();

        $this->structureParent = $structureParent;
        $this->fileStorage = $fileStorage;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->structureFileRepository = $structureFileRepository;
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    protected function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addUpload('file')
            ->setRequired('Please enter file to upload.');

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form)
    {
        $values = $form->getValues();

        if (!$values->file->isOk()) {
            $form->addError('Upload of file failed ' . $values->file->getError());
        }

        if (!$this->presenter->isAllowed('file', 'edit')) {
            $form->addError('Nemáte oprávění editovat robots.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        $structureFile = $this->fileStorage->processFile($values->file, $this->structureParent);

        $this->onSuccess($structureFile);
    }

    public function render()
    {
        $template = $this->template;
        $template->maxUploadSize = Tools::getMaxUploadSize();
        $template->setFile(__DIR__ . '/UploadFileForm.latte');
        $template->render();
    }
}