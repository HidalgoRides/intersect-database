<?php

namespace Intersect\Database\Model\Relationship;

interface Relational {

    public function getEagerRelationshipMap();
    public function getLazyRelationshipMap();

}