<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
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
class StructureFile extends Nette\Object implements IStructureFile
{
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
     * @var ArrayCollection|\Dravencms\Model\Article\Entities\Article[]
     * @ORM\OneToMany(targetEntity="\Dravencms\Model\Article\Entities\Article", mappedBy="structureFile",cascade={"persist"})
     */
    private $articles;

    /**
     * @var ArrayCollection|DownloadFile[]
     * @ORM\OneToMany(targetEntity="DownloadFile", mappedBy="structureFile",cascade={"persist"})
     */
    private $downloadFiles;

    /**
     * StructureFile constructor.
     * @param string $name
     * @param IFile $file
     * @param IStructure $structure
     */
    public function __construct($name, IFile $file, IStructure $structure = null)
    {
        $this->name = $name;
        $this->file = $file;
        $this->structure = $structure;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param IFile $file
     */
    public function setFile(IFile $file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return Structure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @return string
     */
    public function getBasename()
    {
        return $this->name.'.'.$this->file->getExtension();
    }
}