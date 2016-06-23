<?php

namespace NAttreid\Downloader\DI;

/**
 * Rozsireni
 *
 * Attreid <attreid@gmail.com>
 */
class Extension extends \Nette\DI\CompilerExtension {

    /** @var array */
    private $defaults = [
        'temp' => '%tempDir%/index',
    ];

    public function loadConfiguration() {
        $config = $this->validateConfig($this->defaults, $this->config);

        $builder = $this->getContainerBuilder();

        if (!isset($config['path'])) {
            throw new \Nette\InvalidArgumentException("Missing value 'path' for mailing");
        }if (!isset($config['class'])) {
            throw new \Nette\InvalidArgumentException("Missing value 'class' for mailing");
        }

        $builder->addDefinition($this->prefix('downloader.index'))
                ->setClass('\NAttreid\Downloader\IndexFile')
                ->setArguments([$config['temp']]);

        $builder->addDefinition($this->prefix('downloader.downloader'))
                ->setImplement('\NAttreid\Downloader\IDownloader')
                ->setFactory('\NAttreid\Downloader\Downloader')
                ->setAutowired(TRUE);
    }

}
