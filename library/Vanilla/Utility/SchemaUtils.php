<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Invalid;
use Garden\Schema\ValidationField;

/**
 * Utility functions useful for schemas.
 */
final class SchemaUtils {
    /**
     * Return a validation function that will require only N keys be specified in an array.
     *
     * @param array $properties
     * @param int $count
     * @return callable
     */
    public static function onlyOneOf(array $properties, int $count = 1): callable {
        return function ($value, ValidationField $field) use ($properties, $count) {
            if (!ArrayUtils::isArray($value)) {
                return $value;
            }
            $has = [];
            foreach ($properties as $property) {
                if (isset($value[$property])) {
                    $has[] = $property;
                }
            }
            if (count($has) > $count) {
                if ($count === 1) {
                    $message = 'Only one of {properties} are allowed.';
                } else {
                    $message = 'Only {count} of {properties} are allowed.';
                }

                $field->addError('onlyOneOf', [
                    'messageCode' => $message,
                    'properties' => $properties,
                    'count' => $count
                ]);
                return Invalid::value();
            }
            return $value;
        };
    }
}
