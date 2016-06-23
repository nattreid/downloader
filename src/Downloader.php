<?php

namespace NAttreid\Downloader;

use GuzzleHttp\Client,
    GuzzleHttp\Pool,
    GuzzleHttp\Psr7\Request;

/**
 * Stahovani z url
 * 
 * @property-read int $concurrency Maximalni pocet soucasne stahovanych souboru (default = 60)
 * @property-read float $timeout Timeout pro pripojeni k souboru ve vterinach (default = 300)
 * @property-write boolean $index stahovani jen zmenenych souboru
 * @author Attreid <attreid@gmail.com>
 */
class Downloader extends \Nette\Object {

    /** @var IndexFile */
    private $timestamp;

    /** @var Client */
    private $client;

    /** @var int */
    private $concurrency = 60;

    /** @var float */
    private $timeout = 300;

    /** @var boolean */
    private $index = FALSE;

    /** @var array[target => source] */
    private $pool = [];

    /** @var array[target => reason] */
    private $rejected = [];

    /** @var callable[] */
    private $request = [];

    public function __construct(IndexFile $timestamp) {
        $this->timestamp = $timestamp;
        $this->client = new Client;
    }

    /**
     * Nastavi maximalni pocet soucasne stahovanych souboru
     * @param int $concurrency
     */
    public function setConcurrency($concurrency) {
        $this->concurrency = (int) $concurrency;
    }

    /**
     * Nastavi timeout pro pripojeni k souboru
     * @param float $timeout
     */
    public function setTimeout($timeout) {
        $this->timeout = (float) $timeout;
    }

    /**
     * Nastavi stahovani jen zmenenych souboru
     * @param boolean $index
     */
    public function setIndex($index) {
        $this->index = (boolean) $index;
    }

    /**
     * Stazeni z url
     * @param array $pool pole ve formatu (source => target)
     */
    public function download(array $pool) {
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
    private function downloadAll() {
        foreach ($this->pool as $source => $target) {
            $this->addRequest($source, $target);
        }
        $this->request();
    }

    /**
     * Stazeni pouze modifikovanych url
     */
    private function downloadModified() {
        $request = function() {
            foreach ($this->pool as $source => $target) {
                yield $source => new Request('HEAD', $source);
            }
        };
        $pool = new Pool($this->client, $request(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($response, $index) {
                $target = $this->pool[$index];
                $last = $response->getHeader('Last-Modified');
                if (!empty($last)) {
                    if ($this->timestamp->$target != NULL && $this->timestamp->$target == $last[0]) {
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
    private function addRequest($source, $target) {
        $this->request[] = function ($options) use ($source, $target) {
            return $this->client->getAsync($source, [
                        'timeout' => $this->timeout,
                        'sink' => $target
            ]);
        };
    }

    /**
     * Stazeni z url
     */
    private function request() {
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

interface IDownloader {

    /** @return Downloader */
    public function create();
}
