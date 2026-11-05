<?php declare(strict_types=1);
namespace Dravencms\File\Forms;

use Dravencms\Model\File\Entities\StructureFile;
use Nette\Http\FileUpload;

/** Selection is validated by FilePicker; persistence belongs to the consuming plugin. */
final readonly class FileSelection
{
    public function __construct(public ?StructureFile $file, public FileUpload $upload) {}
    public function getId(): ?int { return $this->file?->getId(); }
}
