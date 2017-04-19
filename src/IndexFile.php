<?php

namespace NAttreid\Downloader;

use DateTime;
use Nette\IOException;

/**
 * Trida pro indexaci data stazeni souboru
 *
 * @author Attreid <attreid@gmail.com>
 */
class IndexFile
{
	/** @var string */
	private $file;

	/** @var string[] */
	private $timestamp;

	public function __construct(string $indexFile)
	{
		$this->file = 'nette.safe://' . $indexFile;
	}

	private function getTimestamp(): array
	{
		if ($this->timestamp === null) {
			$data = @file_get_contents($this->file);
			if ($data) {
				$this->timestamp = unserialize($data);
				if (!is_array($this->timestamp)) {
					throw new IOException("Cannot parse file '{$this->file}'");
				}
			} else {
				$this->timestamp = [];
			}
		}
		return $this->timestamp;
	}

	public function isModified(string $name, ?DateTime $timestamp): bool
	{
		$data = $this->getTimestamp();
		if (isset($data[$name])) {
			return $data[$name] != $timestamp;
		} else {
			return true;
		}
	}

	public function save(string $name, DateTime $timestamp): void
	{
		$data = $this->getTimestamp();
		$data[$name] = $timestamp;
		$counter = 0;
		while (!file_put_contents($this->file, serialize($data))) {
			sleep(1);
			if ($counter++ == 10) {
				throw new IOException("Cannot write to file '{$this->file}'");
			}
		}
		$this->timestamp = null;
	}

}
