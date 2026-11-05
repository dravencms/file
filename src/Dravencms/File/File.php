<?php declare(strict_types=1);
namespace Dravencms\File;

use Dravencms\File\Forms\FilePicker;
use Dravencms\Model\File\Repository\StructureFileRepository;
use Nette\Forms\Container;
use Nette\Security\User;
use Salamek\Files\FileStorage;
use Salamek\Files\Access\FileUrl;

final class File
{
    public function __construct(private StructureFileRepository $repository, private FileStorage $storage,
        private FileUrl $urls, private User $user) {}

    public function addSelector(Container $container, string $name, string $label, ?string $type = null, bool $upload = true): FilePicker
    {
        $control = new FilePicker($this->repository, $this->storage, $this->urls, $this->user, $label, $type, $upload);
        $container->addComponent($control, $name);
        return $control;
    }
}
