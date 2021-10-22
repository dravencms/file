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

namespace Dravencms\AdminModule\Components\File\FileForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Components\BaseForm\Form;
use Dravencms\Model\File\Entities\File;
use Dravencms\Model\File\Entities\Structure;
use Dravencms\Model\File\Repository\FileRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;

/**
 * Description of FileForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class FileForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var FileRepository */
    private $fileRepository;

    /** @var User */
    private $user;

    /** @var File|null */
    private $file = null;

    /** @var Structure|null  */
    private $structureParent = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * FileForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param FileRepository $fileRepository
     * @param User $user
     * @param Structure|null $structureParent
     * @param File|null $file
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        FileRepository $fileRepository,
        User $user,
        Structure $structureParent = null,
        File $file = null
    ) {

        $this->structureParent = $structureParent;
        $this->file = $file;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
        $this->user = $user;

        if ($this->file)
        {
            $this['form']->setDefaults([
                'name' => $this->file->getName()
            ]);
        }
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name')
            ->setRequired('Please enter name.')
            ->addRule(Form::MAX_LENGTH, 'Name is too long.', 255);

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();
        if (!$this->fileRepository->isNameFree($values->name, $this->structureParent, $this->file)) {
            $form->addError('Tento název je již zabrán.');
        }

        if (!$this->user->isAllowed('file', 'edit')) {
            $form->addError('Nemáte oprávění editovat robots.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();


        if ($this->file) {
            $file = $this->file;
            $file->setName($values->name);

            $this->entityManager->persist($file);
        } else {
            $file = new File($values->name);
            if ($this->structureParent)
            {
                $this->fileRepository->persistAsLastChildOf($file, $this->structureParent);
            }
            else
            {
                $this->entityManager->persist($file);
            }
        }

        $this->entityManager->flush();

        $this->onSuccess($file);
    }

    public function render(): void
    {
        $template = $this->template;
        $template->structure = $this->file;
        $template->setFile(__DIR__ . '/FileForm.latte');
        $template->render();
    }
}