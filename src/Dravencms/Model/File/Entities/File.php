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

/**
 * Class File
 * @package Dravencms\Model\File\Entities
 * @ORM\Entity
 * @ORM\Table(name="fileFile")
 */
class File implements IFile
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;
    
    /**
     * @var string
     * @ORM\Column(type="string",length=32,unique=true,nullable=false)
     */
    private $sum;

    /**
     * @var integer
     * @ORM\Column(type="integer",nullable=false)
     */
    private $size;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $extension;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $mimeType;

    /**
     * @var string
     * @ORM\Column(type="string",length=255)
     */
    private $type;

    /**
     * @var ArrayCollection|StructureFile[]
     * @ORM\OneToMany(targetEntity="StructureFile", mappedBy="file",cascade={"persist"})
     */
    private $structureFiles;

    /**
     * File constructor.
     * @param string $sum
     * @param int $size
     * @param string $extension
     * @param string $mimeType
     * @param string $type
     */
    public function __construct(string $sum, int $size, string $extension, string $mimeType, string $type)
    {
        $this->sum = $sum;
        $this->size = $size;
        $this->extension = $extension;
        $this->mimeType = $mimeType;
        $this->setType($type);

        $this->structureFiles = new ArrayCollection();
    }

    /**
     * @param string $sum
     * @return void
     */
    public function setSum(string $sum): void
    {
        $this->sum = $sum;
    }

    /**
     * @param int $size
     * @return void
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @param string $extension
     * @return void
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @param string $mimeType
     * @return void
     */
    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type = self::TYPE_BINARY): void
    {
        if (!in_array($type, array(self::TYPE_BINARY, self::TYPE_IMAGE, self::TYPE_MEDIA, self::TYPE_TEXT))) {
            throw new \InvalidArgumentException("Invalid $type");
        }
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSum(): string
    {
        return $this->sum;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getBasename(): string
    {
        return $this->sum.'.'.$this->extension;
    }

    /**
     * @return bool
     */
    public function isExists(): bool
    {
        // TODO: Implement isExists() method.
        return true;
    }

    /**
     * @return StructureFile[]|ArrayCollection
     */
    public function getStructureFiles(): Collection
    {
        return $this->structureFiles;
    }
    
}