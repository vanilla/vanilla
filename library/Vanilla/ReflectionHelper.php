<?php
namespace Vanilla;

/**
 * A helper class for handling reflection deprecated methods
 */
class ReflectionHelper
{
    /**
     * Get a ReflectionClass object for the parameter being reflected or null
     *
     * @param \ReflectionParameter $param
     * @return \ReflectionClass|null
     */
    public static function getClass(\ReflectionParameter $param): ?\ReflectionClass
    {
        $class = null;
        if (PHP_VERSION >= 8) {
            $type = $param->getType();
            $class = $type && !$type->isBuiltin() ? new \ReflectionClass($type->getName()) : null;
        } else {
            $class = $param->getClass();
        }
        return $class;
    }

    /**
     * Check if the reflection parameter is of type array.
     *
     * @param \ReflectionParameter $param
     * @return bool
     */
    public static function isArray(\ReflectionParameter $param): bool
    {
        if (PHP_VERSION >= 8) {
            return $param->getType() && $param->getType()->getName() === "array";
        }
        return $param->isArray();
    }
}
