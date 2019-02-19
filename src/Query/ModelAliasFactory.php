<?php

namespace Intersect\Database\Query;

use Intersect\Database\Query\ModelAlias;
use Intersect\Database\Model\AbstractModel;

class ModelAliasFactory {

    private static $ALIAS_KEY_MAP = [];
    private static $ALIAS_VALUE_MAP = [];
    private static $ALIAS_MAP_INDEX = 0;

    /**
     * @param AbstractModel $model
     * @return string
     */
    public static function generateAlias(AbstractModel $model)
    {
        $value = $model->getTableName();

        if (array_key_exists($value, self::$ALIAS_KEY_MAP))
        {
            return self::$ALIAS_KEY_MAP[$value];
        }

        $newAlias = self::generateNewAlias();

        $modelAlias = new ModelAlias($model, $newAlias);

        self::$ALIAS_KEY_MAP[$value] = $newAlias;
        self::$ALIAS_VALUE_MAP[$newAlias] = $modelAlias;

        return $newAlias;
    }

    /**
     * @param string @alias
     * @return ModelAlias|null
     */
    public static function getAliasValue($alias)
    {
        if (array_key_exists($alias, self::$ALIAS_VALUE_MAP))
        {
            return self::$ALIAS_VALUE_MAP[$alias];
        }

        return null;
    }

    private static function generateNewAlias()
    {
        return 'a' . self::$ALIAS_MAP_INDEX++;
    }

}