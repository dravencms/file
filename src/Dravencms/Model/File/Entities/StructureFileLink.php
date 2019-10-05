<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\File\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\Attributes\UniversallyUniqueIdentifier;
use Nette;
use Salamek\Files\Models\IFile;
use Salamek\Files\Models\IStructure;
use Salamek\Files\Models\IStructureFile;

/**
 * Class StructureFileLink
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileStructureFileLink")
 */
class StructureFileLink
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * What package is using this file
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $packageName;

    /**
     * @var File
     * @ORM\ManyToOne(targetEntity="StructureFile", inversedBy="structureFileLinks")
     * @ORM\JoinColumn(name="strucutre_file_id", referencedColumnName="id")
     */
    private $structureFile;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isUsed;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isAutoclean;

    /**
     * StructureFileLink constructor.
     * @param $packageName
     * @param IStructureFile $structureFile
     */
    public function __construct(string $packageName, IStructureFile $structureFile, bool $isUsed = true, bool $isAutoclean = true)
    {
        $this->packageName = $packageName;
        $this->structureFile = $structureFile;
        $this->isUsed = $isUsed;
        $this->isAutoclean = $isAutoclean;
    }

    /**
     * @param string $packageName
     */
    public function setPackageName($packageName): void
    {
        $this->packageName = $packageName;
    }

    /**
     * @param IStructureFile $structureFile
     */
    public function setStructureFile(IStructureFile $structureFile): void
    {
        $this->structureFile = $structureFile;
    }

    /**
     * @param bool $isUsed
     */
    public function setIsUsed(bool $isUsed): void
    {
        $this->isUsed = $isUsed;
    }

    /**
     * @param bool $isAutoclean
     */
    public function setIsAutoclean(bool $isAutoclean): void
    {
        $this->isAutoclean = $isAutoclean;
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @return StructureFile
     */
    public function getStructureFile(): StructureFile
    {
        return $this->structureFile;
    }

    /**
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    /**
     * @return bool
     */
    public function isAutoclean(): bool
    {
        return $this->isAutoclean;
    }

}