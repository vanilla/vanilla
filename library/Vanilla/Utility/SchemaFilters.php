<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\ValidationField;

/**
 * A collection of field filter functions, compatible with Garden Schema.
 */
class SchemaFilters {

    /**
     * Attempt to decode a encoded string value into a more complex type.
     *
     * @param mixed $value Raw database value.
     * @param ValidationField $field An object representing the Garden Schema field.
     * @return mixed Unpacked attributes field value.
     */
    public static function encodeValue($value, ValidationField $field) {
        if ($value === null || $value === '') {
            return null;
        }
        $result = StringUtils::jsonEncodeChecked($value, JSON_UNESCAPED_SLASHES);
        return $result;
    }

    /**
     * Attempt to decode a encoded string value into a more complex type.
     *
     * @param mixed $value Raw database value.
     * @param ValidationField $field An object representing the Garden Schema field.
     * @return mixed Unpacked attributes field value.
     */
    public static function decodeValue($value, ValidationField $field) {
        if ($value === null || $value === '') {
            $value = null;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
            if ($value === null) {
                try {
                    $value = unserialize($value);
                    // @codeCoverageIgnoreStart
                } catch (\Exception $e) {
                    $value = null;
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        return $value;
    }
}
