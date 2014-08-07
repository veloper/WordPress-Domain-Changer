<?php

class PhpFile {

    public $path        = null;
    public $contents    = null;

    public function __construct($path) {
        $this->path = $path;
    }

    public function getPath() {
        return $this->path;
    }

    public function load() {
        $this->contents = file_get_contents($this->getPath());
    }

    public function getContents() {
        if(!$this->contents) $this->load();
        return $this->contents;
    }

    /**
     * Gets a constant's value
     *
     * @param string - The constant's name
     * @return mixed; the value, or false if not found.
     */
    public function getConstant($name) {
        preg_match("!define\('" . $name . "',[^']*'(.+?)'\);!", $this->getContents(), $matches);
        return (isset($matches[1])) ? $matches[1] : false;
    }

    /**
     * Gets variable's value
     *
     * @param string - The variables's name (without the dollar sign)
     * @return mixed; the value, or false if not found.
     */
    public function getVariable($name) {
        preg_match("!\\\$" . $name . "[^=]*=[^']*'(.+?)';!", $this->getContents(), $matches);
        return (isset($matches[1])) ? $matches[1] : false;
    }

}

