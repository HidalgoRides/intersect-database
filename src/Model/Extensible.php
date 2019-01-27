<?php

namespace Intersect\Database\Model;

interface Extensible {

    public function addMetaData($key, $value);
    public function clearAllMetaData();
    public function getMetaData();
    public function getMetaDataByKey($key);
    public function setMetaData(array $metaData);

}