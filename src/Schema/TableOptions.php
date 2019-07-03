<?php

namespace Intersect\Database\Schema;

class TableOptions {

    private $characterSet = 'utf8';
    private $collation = 'utf8_unicode_ci';
    private $engine = 'InnoDB';

    public function getCharacterSet()
    {
        return $this->characterSet;
    }

    public function setCharacterSet($characterSet)
    {
        $this->characterSet = $characterSet;
    }

    public function getCollation()
    {
        return $this->collation;
    }

    public function setCollation($collation)
    {
        $this->collation = $collation;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

}