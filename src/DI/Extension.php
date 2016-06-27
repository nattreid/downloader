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

        $builder->addDefinition($this->prefix('downloader.index'))
                ->setClass('NAttreid\Downloader\IndexFile')
                ->setArguments([$config['temp']]);

        $builder->addDefinition($this->prefix('downloader.downloader'))
                ->setImplement('NAttreid\Downloader\IDownloader')
                ->setFactory('NAttreid\Downloader\Downloader');
    }

}
