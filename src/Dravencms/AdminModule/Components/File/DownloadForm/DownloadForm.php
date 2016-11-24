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

namespace Dravencms\AdminModule\Components\File\DownloadForm;

use Dravencms\Components\BaseFormFactory;
use Dravencms\Model\File\Entities\Download;
use Dravencms\Model\File\Repository\DownloadRepository;
use App\Model\Locale\Repository\LocaleRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * Description of DownloadForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class DownloadForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var DownloadRepository */
    private $downloadRepository;

    /** @var LocaleRepository */
    private $localeRepository;

    /** @var Download|null */
    private $download = null;

    /** @var array */
    public $onSuccess = [];

    /**
     * DownloadForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param DownloadRepository $downloadRepository
     * @param LocaleRepository $localeRepository
     * @param Download|null $download
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        DownloadRepository $downloadRepository,
        LocaleRepository $localeRepository,
        Download $download = null
    ) {
        parent::__construct();

        $this->download = $download;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->downloadRepository = $downloadRepository;
        $this->localeRepository = $localeRepository;


        if ($this->download) {
            $defaults = [
                /*'name' => $this->download->getName(),
                'description' => $this->download->getDescription(),*/
                'isShowName' => $this->download->isShowName()
            ];

            $repository = $this->entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
            $defaults += $repository->findTranslations($this->download);

            $defaultLocale = $this->localeRepository->getDefault();
            if ($defaultLocale) {
                $defaults[$defaultLocale->getLanguageCode()]['name'] = $this->download->getName();
                $defaults[$defaultLocale->getLanguageCode()]['description'] = $this->download->getDescription();
            }

        }
        else{
            $defaults = [
                'isShowName' => false
            ];
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
                ->setRequired('Please enter download name.')
                ->addRule(Form::MAX_LENGTH, 'Download name is too long.', 255);

            $container->addTextArea('description');
        }

        $form->addCheckbox('isShowName');

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
            if (!$this->downloadRepository->isNameFree($values->{$activeLocale->getLanguageCode()}->name, $activeLocale, $this->download)) {
                $form->addError('Tento název je již zabrán.');
            }
        }

        if (!$this->presenter->isAllowed('file', 'downloadEdit')) {
            $form->addError('Nemáte oprávění editovat download.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form)
    {
        $values = $form->getValues();


        if ($this->download) {
            $download = $this->download;
            /*$download->setName($values->name);
            $download->setDescription($values->description);*/
            $download->setIsShowName($values->isShowName);
        } else {
            $defaultLocale = $this->localeRepository->getDefault();
            $download = new Download($values->{$defaultLocale->getLanguageCode()}->name, $values->{$defaultLocale->getLanguageCode()}->description, $values->isShowName);
        }

        $repository = $this->entityManager->getRepository('Gedmo\\Translatable\\Entity\\Translation');

        foreach ($this->localeRepository->getActive() AS $activeLocale) {
            $repository->translate($download, 'name', $activeLocale->getLanguageCode(), $values->{$activeLocale->getLanguageCode()}->name)
                ->translate($download, 'description', $activeLocale->getLanguageCode(), $values->{$activeLocale->getLanguageCode()}->description);
        }
        
        $this->entityManager->persist($download);

        $this->entityManager->flush();

        $this->onSuccess();
    }


    public function render()
    {
        $template = $this->template;
        $template->activeLocales = $this->localeRepository->getActive();
        $template->setFile(__DIR__ . '/DownloadForm.latte');
        $template->render();
    }
}