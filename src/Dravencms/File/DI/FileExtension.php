<?php declare(strict_types = 1);

namespace Dravencms\File\DI;

use Dravencms\File\File;
use Contributte\Translation\DI\TranslationProviderInterface;
use Nette\DI\CompilerExtension;

/**
 * Class FileExtension
 * @package Dravencms\File\DI
 */
class FileExtension extends CompilerExtension implements TranslationProviderInterface
{
    public function getTranslationResources(): array
    {
        return [__DIR__.'/../lang'];
    }


    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();


        $builder->addDefinition($this->prefix('file'))
            ->setClass(File::class);

        $this->loadComponents();
        $this->loadModels();
        $this->loadConsole();
    }


    protected function loadComponents(): void
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->loadFromFile(__DIR__ . '/components.neon') as $i => $command) {
            $cli = $builder->addFactoryDefinition($this->prefix('components.' . $i));
            if (is_string($command)) {
                $cli->setImplement($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function loadModels(): void
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->loadFromFile(__DIR__ . '/models.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('models.' . $i));
            if (is_string($command)) {
                $cli->setFactory($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function loadConsole(): void
    {
        $builder = $this->getContainerBuilder();

        foreach ($this->loadFromFile(__DIR__ . '/console.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('cli.' . $i))
                ->setAutowired(false);

            if (is_string($command)) {
                $cli->setFactory($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }
}
