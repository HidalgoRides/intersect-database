<?php

namespace Intersect\Database\Migrations;

class MigrationHelper {

    public static function resolveClassNameFromPath($path)
    {
        // remove extension
        $className = str_replace('.php', '', basename($path));

        // remove unneeded date details
        $className = implode('_', array_slice(explode('_', $className), 4));

        // uppercase first letter and all letters after an underscore
        $className = ucwords($className, '_');

        // remove remaining underscores
        $className = str_replace('_', '', $className);

        return $className;
    }

}