<?php

namespace Intersect\Database\Model;

class ModelHelper {

    public static function normalizeList(array $modelList = [], $convertAttributeKeys = false)
    {
        $normalizedList = [];

        /** @var Model $model */
        foreach ($modelList as $model)
        {
            $normalizedList[] = self::normalize($model, $convertAttributeKeys);
        }

        return $normalizedList;
    }

    public static function normalize(Model $model = null, $convertAttributeKeys = false)
    {
        $normalizedModel = null;

        if (!is_null($model))
        {
            $normalizedModel = $model->normalize($convertAttributeKeys);
        }

        return $normalizedModel;
    }

}