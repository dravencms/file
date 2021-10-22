<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Nette;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IStructure;
use Salamek\Files\Models\IStructureFile;

/**
 * Class StructureFile
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileStructureFile")
 */
class StructureFile implements IStructureFile
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $name;

    /**
     * @var File
     * @ORM\ManyToOne(targetEntity="File", inversedBy="structureFiles")
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id")
     */
    private $file;

    /**
     * @var Structure
     * @ORM\ManyToOne(targetEntity="Structure", inversedBy="structureFiles")
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id",nullable=true)
     */
    private $structure;


    /**
     * @var ArrayCollection|StructureFileLink[]
     * @ORM\OneToMany(targetEntity="StructureFileLink", mappedBy="structureFile",cascade={"persist"})
     */
    private $structureFileLinks;

    /**
     * StructureFile constructor.
     * @param string $name
     * @param IFile $file
     * @param IStructure $structure
     */
    public function __construct(string $name, IFile $file, IStructure $structure = null)
    {
        $this->name = $name;
        $this->file = $file;
        $this->structure = $structure;
        $this->structureFileLinks = new ArrayCollection();
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param IFile $file
     */
    public function setFile(IFile $file): void
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return File
     */
    public function getFile(): IFile
    {
        return $this->file;
    }

    /**
     * @return Structure
     */
    public function getStructure(): IStructure
    {
        return $this->structure;
    }

    /**
     * @return string
     */
    public function getBasename(): string
    {
        return $this->name.'.'.$this->file->getExtension();
    }

    /**
     * @return ArrayCollection|StructureFileLink[]
     */
    public function getStructureFileLinks(): Collection
    {
        return $this->structureFileLinks;
    }
}