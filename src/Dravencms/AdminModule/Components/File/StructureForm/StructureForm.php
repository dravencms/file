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

namespace Dravencms\AdminModule\Components\File\StructureForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\File\Entities\Structure;
use Dravencms\Model\File\Repository\StructureRepository;
use Dravencms\Database\EntityManager;
use Dravencms\Components\BaseForm\Form;
use Nette\Security\User;

/**
 * Description of RobotsForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class StructureForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var User */
    private $user;

    /** @var StructureRepository */
    private $structureRepository;

    /** @var Structure|null */
    private $structure = null;

    /** @var Structure|null  */
    private $structureParent = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * StructureForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param User $user
     * @param StructureRepository $structureRepository
     * @param Structure|null $structureParent
     * @param Structure|null $structure
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        User $user,
        StructureRepository $structureRepository,
        Structure $structureParent = null,
        Structure $structure = null
    ) {
        $this->structureParent = $structureParent;
        $this->structure = $structure;
        $this->user = $user;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->structureRepository = $structureRepository;

        if ($this->structure)
        {
            $this['form']->setDefaults([
                'name' => $this->structure->getName()
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();
        if (!$this->structureRepository->isNameFree($values->name, $this->structureParent, $this->structure)) {
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


        if ($this->structure) {
            $structure = $this->structure;
            $structure->setName($values->name);

            $this->entityManager->persist($structure);
        } else {
            $structure = new Structure($values->name);
            if ($this->structureParent)
            {
                $this->structureRepository->persistAsLastChildOf($structure, $this->structureParent);
            }
            else
            {
                $this->entityManager->persist($structure);
            }
        }

        $this->entityManager->flush();

        $this->onSuccess($structure);
    }

    public function render(): void
    {
        $template = $this->template;
        $template->structure = $this->structure;
        $template->setFile(__DIR__ . '/StructureForm.latte');
        $template->render();
    }
}