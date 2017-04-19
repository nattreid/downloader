<?php

declare(strict_types=1);

namespace NAttreid\Downloader\DI;

use NAttreid\Downloader\Downloader;
use NAttreid\Downloader\IDownloaderFactory;
use NAttreid\Downloader\IndexFile;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;

/**
 * Rozsireni
 *
 * Attreid <attreid@gmail.com>
 */
class DownloaderExtension extends CompilerExtension
{

	/** @var array */
	private $defaults = [
		'temp' => '%tempDir%/index',
	];

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->config);

		$config['temp'] = Helpers::expand($config['temp'], $builder->parameters);

		$builder->addDefinition($this->prefix('downloader.index'))
			->setClass(IndexFile::class)
			->setArguments([$config['temp']]);

		$builder->addDefinition($this->prefix('downloader.downloader'))
			->setImplement(IDownloaderFactory::class)
			->setFactory(Downloader::class);
	}

}
