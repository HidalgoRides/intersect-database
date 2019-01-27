<?php

namespace Intersect\Database\Query;

class AliasFactory {

    private static $ALIAS_KEY_MAP = [];
    private static $ALIAS_VALUE_MAP = [];
    private static $ALIAS_MAP_INDEX = 0;

    public static function getAlias($value)
    {
        if (array_key_exists($value, self::$ALIAS_KEY_MAP))
        {
            return self::$ALIAS_KEY_MAP[$value];
        }

        $newAlias = self::generateAlias();

        self::$ALIAS_KEY_MAP[$value] = $newAlias;
        self::$ALIAS_VALUE_MAP[$newAlias] = $value;

        return self::$ALIAS_KEY_MAP[$value];
    }

    public static function getAliasValue($alias)
    {
        if (array_key_exists($alias, self::$ALIAS_VALUE_MAP))
        {
            return self::$ALIAS_VALUE_MAP[$alias];
        }

        return null;
    }

    private static function generateAlias()
    {
        return 'a' . self::$ALIAS_MAP_INDEX++;
    }

}