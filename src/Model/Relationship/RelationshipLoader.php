<?php

namespace Intersect\Database\Model\Relationship;

use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Model\Model;

class RelationshipLoader {

    private static $RELATIONSHIP_CACHE = [];

    /**
     * @param Model $model
     * @param Relationship $relationship
     * @param $relationshipKey
     * @throws DatabaseException
     */
    public static function loadLazyRelationship(Model $model, Relationship $relationship, $relationshipKey)
    {
        $modelAttributeValue = $model->getAttribute($relationship->getColumn());
        $relationshipModelClass = $relationship->getModelClass();
        $model->setRelationship($relationshipKey, null);

        $cacheKey = self::generateCacheKey($relationshipModelClass, $relationshipKey, $modelAttributeValue);

        if (array_key_exists($cacheKey, self::$RELATIONSHIP_CACHE))
        {
            $model->setRelationship($relationshipKey, self::$RELATIONSHIP_CACHE[$cacheKey]);
        }
        else
        {
            /** @var Model $relationshipModelClass */
            $relationshipModel = $relationshipModelClass::findById($modelAttributeValue);
            if (!is_null($relationshipModel))
            {
                $model->setRelationship($relationshipKey, $relationshipModel);
                self::$RELATIONSHIP_CACHE[$cacheKey] = $relationshipModel;
            }
        }
    }

    private static function generateCacheKey($className, $attributeKey, $value)
    {
        return md5($className . $attributeKey . $value);
    }

}