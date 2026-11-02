<?php declare(strict_types=1);

namespace Dravencms\File\Forms;

use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Form;
use Nette\Forms\Rules;
use Nette\Forms\Validator;

final class UploadMessages
{
    public static function localize(Rules $rules): void
    {
        // Nette creates an upload-error condition and a size rule automatically.
        foreach ($rules as $rule) {
            if ($rule->branch !== null) {
                self::localize($rule->branch);
            } elseif ($rule->validator === Form::MaxFileSize) {
                $rule->message = 'file.uploadMaximumSize';
            } elseif ($rule->message === Validator::$messages[UploadControl::Valid]) {
                $rule->message = 'file.uploadOfFileFailed';
            }
        }
    }
}
