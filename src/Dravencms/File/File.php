<?php

namespace Dravencms\File;

use Nette;

/**
 * Class File
 * @package Dravencms\File
 */
class File
{
    use Nette\SmartObject;

    public function getFileSelectorPath()
    {
        return __DIR__.'/../AdminModule/templates/File/File/fileSelector.latte';
    }
}
