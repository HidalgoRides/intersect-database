<?php

namespace Intersect\Database\Model\Traits;

trait HasMetaData {

    protected $metaDataColumn = 'meta_data';
    private $metaData = null;

    /**
     * @param $key
     * @param $value
     */
    public function addMetaData($key, $value)
    {
        $this->metaData[$key] = $value;
    }

    public function clearAllMetaData()
    {
        $metaDataAttribute = (array_key_exists($this->metaDataColumn, $this->attributes)) ? $this->attributes[$this->metaDataColumn] : null;

        if (!is_null($metaDataAttribute))
        {
            $this->attributes[$this->metaDataColumn] = null;
        }

        $this->metaData = null;
    }

    /**
     * @param $key
     */
    public function clearMetaDataByKey($key)
    {
        if (!is_null($this->metaData))
        {
            if (array_key_exists($key, $this->metaData))
            {
                unset($this->metaData[$key]);
            }
        }
    }

    /**
     * @param array $metaData
     */
    public function setMetaData(array $metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * @return array|null
     */
    public function getMetaData()
    {
        if (is_null($this->metaData))
        {
            $metaDataAttribute = (array_key_exists($this->metaDataColumn, $this->attributes)) ? $this->attributes[$this->metaDataColumn] : null;

            if (!is_null($metaDataAttribute))
            {
                $this->metaData = unserialize($metaDataAttribute);
            }
        }

        return $this->metaData;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getMetaDataByKey($key)
    {
        $metaData = $this->getMetaData();

        if (is_null($metaData))
        {
            return null;
        }

        return (array_key_exists($key, $metaData)) ? $metaData[$key] : null;
    }

}