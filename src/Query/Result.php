<?php

namespace Intersect\Database\Query;

class Result {

    private $affectedRows = 0;
    private $count = 0;
    private $insertId;
    private $records = [];

    /**
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    /**
     * @param int $affectedRows
     */
    public function setAffectedRows(int $affectedRows)
    {
        $this->affectedRows = $affectedRows;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return mixed
     */
    public function getInsertId()
    {
        return $this->insertId;
    }

    /**
     * @param mixed $insertId
     */
    public function setInsertId($insertId)
    {
        $this->insertId = $insertId;
    }

    /**
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param array $records
     */
    public function setRecords(array $records)
    {
        $this->records = $records;
        $this->count = count($records);
    }

}