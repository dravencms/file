<?php declare(strict_types=1);
namespace Dravencms\File\Forms;

use Dravencms\Model\File\Entities\StructureFile;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Security\User;
use Nette\Utils\Html;
use Salamek\Files\{FileStorage, Models\IFile};
use Salamek\Files\Access\{FileReference, FileUrl};

final class FilePicker extends BaseControl
{
    private ?int $selectedId = null;
    private ?int $defaultId = null;
    private bool $invalidId = false;
    private ?FileUpload $upload = null;

    public function __construct(private StructureFileRepository $repository, private FileStorage $storage,
        private FileUrl $urls, private User $user, string $label, private ?string $fileType = null,
        private bool $allowUpload = true)
    {
        parent::__construct($label);
        $this->getLabelPrototype()->class = 'form-label';
        $this->setOption('type', 'filePicker');
        $this->monitor(Form::class, function (Form $form): void {
            if ($this->allowUpload) {
                if (!$form->isMethod('post')) { throw new \LogicException('File selection uploads require POST.'); }
                $form->getElementPrototype()->enctype = 'multipart/form-data';
            }
        });
    }

    public function setValue(mixed $value)
    {
        $value = $value instanceof FileSelection ? $value->getId() : ($value instanceof StructureFile ? $value->getId() : $value);
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->invalidId = $value !== null && $value !== '' && $id === false;
        $this->selectedId = $id === false ? null : $id;
        $this->upload = null;
        return $this;
    }

    public function setExistingFile(?StructureFile $file): static
    {
        $this->defaultId = $file?->getId();
        return $this;
    }

    public function loadHttpData(): void
    {
        $value = $this->getHttpData(Form::DataText, '[id]');
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->invalidId = $value !== null && $value !== '' && $id === false;
        $this->selectedId = $id === false ? null : $id;
        $this->upload = $this->allowUpload ? $this->getHttpData(Form::DataFile, '[upload]') : null;
    }

    public function getValue(): FileSelection
    {
        return new FileSelection($this->selectedId === null ? null : $this->repository->getOneById($this->selectedId), $this->upload ?? new FileUpload(null));
    }

    public function isFilled(): bool
    {
        return $this->selectedId !== null || ($this->upload !== null && $this->upload->hasFile());
    }

    public function validate(): void
    {
        parent::validate();
        if ($this->isDisabled()) { return; }
        $selection = $this->getValue();
        if ($this->invalidId || ($this->selectedId !== null && $selection->file === null)) {
            $this->addError('file.picker.invalidSelection');
        }
        // Editing an attachment already owned by the form does not require browsing the file manager.
        if ($this->selectedId !== null && $this->selectedId !== $this->defaultId && !$this->user->isAllowed('file', 'edit')) {
            $this->addError('file.picker.selectionForbidden');
        }
        if ($selection->file && $this->fileType !== null && $selection->file->getFile()->getType() !== $this->fileType) {
            $this->addError('file.picker.wrongType');
        }
        $upload = $selection->upload;
        if ($upload->getError() !== UPLOAD_ERR_NO_FILE) {
            if (!$upload->isOk()) {
                $this->addError('file.uploadOfFileFailed');
            } elseif ($upload->getSize() > \Nette\Forms\Helpers::iniGetSize('upload_max_filesize')) {
                $this->addError('file.uploadError.tooLarge');
            } elseif ($this->fileType !== null && !$this->storage->isFileType($upload->getTemporaryFile(), $this->fileType)) {
                $this->addError('file.picker.wrongType');
            }
        }
    }

    public function getControl(): Html
    {
        $this->setOption('rendered', true);
        $presenter = $this->lookup(Presenter::class);
        $selection = $this->getValue();
        $wrapper = Html::el('div', [
            'data-file-picker' => true, 'data-file-type' => $this->fileType ?? '',
            'data-browser-url' => $presenter->link(':Admin:File:File:ajaxFileManagerSelector'),
            'data-preview-url' => $presenter->link(':Admin:File:File:ajaxFileSelection'),
            'data-error-label' => $this->translate('file.picker.loadFailed'),
        ]);
        $wrapper->addHtml(Html::el('input', ['type' => 'hidden', 'name' => $this->getHtmlName() . '[id]',
            'id' => $this->getHtmlId(), 'value' => $this->selectedId, 'disabled' => $this->isDisabled(), 'data-file-picker-id' => true]));
        $buttons = $wrapper->create('div', ['class' => 'd-flex gap-2 mb-2 flex-wrap']);
        $buttons->addHtml(Html::el('button', ['type' => 'button', 'class' => 'btn btn-success', 'data-file-picker-browse' => true,
            'disabled' => $this->isDisabled() || !$this->user->isAllowed('file', 'edit')])->setText($this->translate('file.selectFileFromFileManager')));
        if ($this->allowUpload) {
            $accept = match ($this->fileType) { IFile::TYPE_IMAGE => 'image/*', IFile::TYPE_TEXT => 'text/*', IFile::TYPE_MEDIA => 'audio/*,video/*', default => null };
            $label = $buttons->create('label', ['class' => 'btn btn-outline-secondary']);
            $label->addText($this->translate('file.uploadFromComputer'));
            $label->addHtml(Html::el('input', ['type' => 'file', 'name' => $this->getHtmlName() . '[upload]', 'accept' => $accept,
                'class' => 'visually-hidden', 'disabled' => $this->isDisabled(), 'data-file-picker-upload' => true]));
        }
        $buttons->addHtml(Html::el('button', ['type' => 'button', 'class' => 'btn btn-outline-danger', 'data-file-picker-clear' => true,
            'disabled' => $this->isDisabled()])->setText($this->translate('file.removeSelection')));
        $preview = $wrapper->create('div', ['data-file-picker-preview' => true, 'aria-live' => 'polite']);
        if ($selection->file) {
            $file = $selection->file->getFile();
            if ($file->getType() === IFile::TYPE_IMAGE) {
                $preview->addHtml(Html::el('img', ['src' => $this->urls->create(new FileReference('file.admin', (string) $file->getId(), $file), 'admin-thumb'),
                    'alt' => '', 'class' => 'img-thumbnail', 'style' => 'max-height:200px']));
            }
            $preview->create('span', ['class' => 'ms-2'])->setText($selection->file->getName() . '.' . $file->getExtension());
        }
        return $wrapper;
    }
}
