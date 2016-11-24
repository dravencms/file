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

namespace Dravencms\AdminModule\Components\File\DownloadFileForm;

use Dravencms\Components\BaseFormFactory;
use Dravencms\Model\File\Entities\Download;
use Dravencms\Model\File\Entities\DownloadFile;
use Dravencms\Model\File\Repository\DownloadFileRepository;
use Dravencms\Model\File\Repository\StructureFileRepository;
use App\Model\Locale\Repository\LocaleRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * Description of DownloadFileForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadFileForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var DownloadFileRepository */
    private $fileRepository;
    
    /** @var StructureFileRepository */
    private $structureFileRepository;

    /** @var LocaleRepository */
    private $localeRepository;

    /** @var Download */
    private $download;

    /** @var DownloadFile|null */
    private $file = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * DownloadFileForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param DownloadFileRepository $fileRepository
     * @param StructureFileRepository $structureFileRepository
     * @param LocaleRepository $localeRepository
     * @param Download $download
     * @param DownloadFile|null $file
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        DownloadFileRepository $fileRepository,
        StructureFileRepository $structureFileRepository,
        LocaleRepository $localeRepository,
        Download $download,
        DownloadFile $file = null
    ) {
        parent::__construct();

        $this->download = $download;
        $this->file = $file;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->fileRepository = $fileRepository;
        $this->localeRepository = $localeRepository;
        $this->structureFileRepository = $structureFileRepository;


        if ($this->file) {
            
            $defaults = [
                'name' => $this->file->getName(),
                'description' => $this->file->getDescription(),
                'position' => $this->file->getPosition(),
                'structureFile' => $this->file->getStructureFile()->getId()
            ];

            $repository = $this->entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
            $defaults += $repository->findTranslations($this->file);

            $defaultLocale = $this->localeRepository->getDefault();
            if ($defaultLocale) {
                $defaults[$defaultLocale->getLanguageCode()]['name'] = $this->file->getName();
                $defaults[$defaultLocale->getLanguageCode()]['description'] = $this->file->getDescription();
            }

        }
        else{
            $defaults = [];
        }

        $this['form']->setDefaults($defaults);
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    protected function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        foreach ($this->localeRepository->getActive() as $activeLocale) {
            $container = $form->addContainer($activeLocale->getLanguageCode());
            $container->addText('name')
                ->setRequired('Please enter file name.')
                ->addRule(Form::MAX_LENGTH, 'File name is too long.', 255);

            $container->addTextArea('description');
        }

        $form->addText('structureFile')
            ->setType('number')
            ->setRequired('Please select the photo.');

        $form->addText('position')
            ->setDisabled(is_null($this->file));

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

        foreach ($this->localeRepository->getActive() AS $activeLocale) {
            if (!$this->fileRepository->isNameFree($values->{$activeLocale->getLanguageCode()}->name, $activeLocale, $this->download, $this->file)) {
                $form->addError('Tento název je již zabrán.');
            }
        }

        if (!$this->presenter->isAllowed('file', 'downloadEdit')) {
            $form->addError('Nemáte oprávění editovat download file.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        $structureFile = $this->structureFileRepository->getOneById($values->structureFile);

        if ($this->file) {
            $item = $this->file;
            /*$item->setName($values->name);
            $item->setDescription($values->description);*/
            $item->setPosition($values->position);
            $item->setStructureFile($structureFile);
        } else {
            $defaultLocale = $this->localeRepository->getDefault();
            $item = new DownloadFile($structureFile, $this->download, $values->{$defaultLocale->getLanguageCode()}->name, $values->{$defaultLocale->getLanguageCode()}->description);
        }

        $repository = $this->entityManager->getRepository('Gedmo\\Translatable\\Entity\\Translation');

        foreach ($this->localeRepository->getActive() AS $activeLocale) {
            $repository->translate($item, 'name', $activeLocale->getLanguageCode(), $values->{$activeLocale->getLanguageCode()}->name)
                ->translate($item, 'description', $activeLocale->getLanguageCode(), $values->{$activeLocale->getLanguageCode()}->description);
        }

        $this->entityManager->persist($item);

        $this->entityManager->flush();

        $this->onSuccess($item);
    }


    public function render()
    {
        $template = $this->template;
        $template->activeLocales = $this->localeRepository->getActive();
        $template->setFile(__DIR__ . '/DownloadFileForm.latte');
        $template->render();
    }
}