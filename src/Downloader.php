<?php

declare(strict_types = 1);

namespace NAttreid\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Nette\SmartObject;
use Psr\Http\Message\ResponseInterface;

/**
 * Stahovani z url
 *
 * @property-write int $concurrency Maximalni pocet soucasne stahovanych souboru (default = 60)
 * @property-write float $timeout Timeout pro pripojeni k souboru ve vterinach (default = 300)
 * @property-write bool $index stahovani jen zmenenych souboru
 * @author Attreid <attreid@gmail.com>
 */
class Downloader
{

	use SmartObject;

	/** @var IndexFile */
	private $timestamp;

	/** @var Client */
	private $client;

	/** @var int */
	private $concurrency = 60;

	/** @var float */
	private $timeout = 300;

	/** @var bool */
	private $index = false;

	/** @var string[] */
	private $pool = [];

	/** @var string[] */
	private $rejected = [];

	/** @var callable[] */
	private $request = [];

	public function __construct(IndexFile $timestamp)
	{
		$this->timestamp = $timestamp;
		$this->client = new Client;
	}

	/**
	 * Nastavi maximalni pocet soucasne stahovanych souboru
	 * @param int $concurrency
	 */
	protected function setConcurrency(int $concurrency)
	{
		$this->concurrency = $concurrency;
	}

	/**
	 * Nastavi timeout pro pripojeni k souboru
	 * @param float $timeout
	 */
	protected function setTimeout(float $timeout)
	{
		$this->timeout = $timeout;
	}

	/**
	 * Nastavi stahovani jen zmenenych souboru
	 * @param bool $index
	 */
	protected function setIndex(bool $index)
	{
		$this->index = $index;
	}

	/**
	 * Stazeni z url
	 * @param array $pool pole ve formatu (source => target)
	 */
	public function download(array $pool)
	{
		$this->pool = $pool;

		if ($this->index) {
			$this->downloadModified();
		} else {
			$this->downloadAll();
		}
	}

	/**
	 * Stazeni vsech url
	 */
	private function downloadAll()
	{
		foreach ($this->pool as $source => $target) {
			$this->addRequest($source, $target);
		}
		$this->request();
	}

	/**
	 * Stazeni pouze modifikovanych url
	 */
	private function downloadModified()
	{
		$request = function () {
			foreach ($this->pool as $source => $target) {
				yield $source => new Request('HEAD', $source);
			}
		};
		$pool = new Pool($this->client, $request(), [
			'concurrency' => $this->concurrency,
			'fulfilled' => function (ResponseInterface $response, $index) {
				$target = $this->pool[$index];
				$last = $response->getHeader('Last-Modified');
				if (!empty($last)) {
					if ($this->timestamp->$target != null && $this->timestamp->$target == $last[0]) {
						return;
					}
					$this->timestamp->$target = $last[0];
				}
				$this->addRequest($index, $target);
			},
			'rejected' => function ($reason, $index) {
				$this->rejected[$index] = $reason;
			},
			'options' => [
				'timeout' => $this->timeout,
			]
		]);

		$promise = $pool->promise();
		$promise->wait();

		$this->request();
	}

	/**
	 * pridani adresy ke stazeni
	 * @param string $source
	 * @param string $target
	 */
	private function addRequest(string $source, string $target)
	{
		$this->request[] = function () use ($source, $target) {
			return $this->client->getAsync($source, [
				'timeout' => $this->timeout,
				'sink' => $target
			]);
		};
	}

	/**
	 * Stazeni z url
	 */
	private function request()
	{
		$pool = new Pool($this->client, $this->request, [
			'concurrency' => $this->concurrency,
			'fulfilled' => function ($response, $index) {

			},
			'rejected' => function ($reason, $index) {

			},
		]);
		$promise = $pool->promise();
		$promise->wait();
	}

}

interface IDownloader
{
	public function create(): Downloader;
}
