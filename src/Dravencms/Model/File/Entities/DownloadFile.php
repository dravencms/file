<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Gedmo\Sortable\Sortable;

/**
 * Class Download
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileDownloadFile")
 */
class DownloadFile extends Nette\Object
{
    use Identifier;
    use TimestampableEntity;

    /**
     * @var StructureFile
     * @ORM\ManyToOne(targetEntity="\Dravencms\Model\File\Entities\StructureFile", inversedBy="downloadFiles")
     * @ORM\JoinColumn(name="structure_file_id", referencedColumnName="id")
     */
    private $structureFile;

    /**
     * @var Download
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Download", inversedBy="downloadFiles")
     * @ORM\JoinColumn(name="download_id", referencedColumnName="id")
     */
    private $download;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $name;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $description;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $downloadCount;

    /**
     * @var integer
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     * and it is not necessary because globally locale can be set in listener
     */
    private $locale;

    /**
     * DownloadFile constructor.
     * @param StructureFile $structureFile
     * @param Download $download
     * @param string $name
     * @param string $description
     */
    public function __construct(StructureFile $structureFile, Download $download, $name, $description)
    {
        $this->structureFile = $structureFile;
        $this->download = $download;
        $this->name = $name;
        $this->description = $description;
        $this->downloadCount = 0;
    }
    
    /**
     * @param StructureFile $structureFile
     */
    public function setStructureFile($structureFile)
    {
        $this->structureFile = $structureFile;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param int $downloadCount
     */
    public function setDownloadCount($downloadCount)
    {
        $this->downloadCount = $downloadCount;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return StructureFile
     */
    public function getStructureFile()
    {
        return $this->structureFile;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getDownloadCount()
    {
        return $this->downloadCount;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return Download
     */
    public function getDownload()
    {
        return $this->download;
    }
}