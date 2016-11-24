<?php

namespace Dravencms\File;


/**
 * Class File
 * @package Dravencms\File
 */
class File extends \Nette\Object
{

    public function __construct()
    {
    }

    public function getFileSelectorPath()
    {
        return __DIR__.'/../AdminModule/templates/File/File/fileSelector.latte';
    }
}
