<?php

namespace NAttreid\Downloader;

/**
 * Trida pro indexaci data stazeni souboru
 * 
 * @author Attreid <attreid@gmail.com>
 */
class IndexFile {

    private $file;

    public function __construct($indexFile) {
        $this->file = 'nette.safe://' . $indexFile;
    }

    private function readFile() {
        $data = @file_get_contents($this->file);
        if (!$data) {
            $timestamp = [];
        } else {
            $timestamp = unserialize($data);
            if (!is_array($timestamp)) {
                throw new \Nette\IOException("Cannot parse file '{$this->file}'");
            }
        }
        return $timestamp;
    }

    public function __get($name) {
        $timestamp = $this->readFile();
        if (isset($timestamp[$name])) {
            return $timestamp[$name];
        } else {
            return NULL;
        }
    }

    public function __set($name, $value) {
        $timestamp = $this->readFile();
        $timestamp[$name] = $value;
        $counter = 0;
        while (!file_put_contents($this->file, serialize($timestamp))) {
            sleep(1);
            if ($counter++ == 10) {
                throw new \Nette\IOException("Cannot write to file '{$this->file}'");
            }
        }
    }

}