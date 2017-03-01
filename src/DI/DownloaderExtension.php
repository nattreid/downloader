<?php

declare(strict_types = 1);

namespace NAttreid\Downloader\DI;

use NAttreid\Downloader\Downloader;
use NAttreid\Downloader\IDownloader;
use NAttreid\Downloader\IndexFile;
use Nette\DI\CompilerExtension;

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

	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('downloader.index'))
			->setClass(IndexFile::class)
			->setArguments([$config['temp']]);

		$builder->addDefinition($this->prefix('downloader.downloader'))
			->setImplement(IDownloader::class)
			->setFactory(Downloader::class);
	}

}
