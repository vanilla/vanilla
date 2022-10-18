<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;

/**
 * Utility functions useful for schemas.
 */
final class SchemaUtils
{
    /**
     * Return a validation function that will require only N keys be specified in an array.
     *
     * @param array $properties
     * @param int $count
     * @return callable
     */
    public static function onlyOneOf(array $properties, int $count = 1): callable
    {
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
                    $message = "Only one of {properties} are allowed.";
                } else {
                    $message = "Only {count} of {properties} are allowed.";
                }

                $field->addError("onlyOneOf", [
                    "messageCode" => $message,
                    "properties" => $properties,
                    "count" => $count,
                ]);
                return Invalid::value();
            }
            return $value;
        };
    }

    /**
     * Validate each row of an array in place.
     *
     * Although schemas can validate arrays, this can prove to be inefficient because the schema returns a new copy of
     * the validated array. This helper will validate the array in place which can be useful when the schema is being
     * used to clean known good data, such as that coming from a database.
     *
     * @param mixed $array The array to validate.
     * @param Schema $schema The schema to validate against.
     * @param bool $sparse Whether or not to do a sparse validation.
     */
    public static function validateArray(&$array, Schema $schema, bool $sparse = false)
    {
        if (!is_array($array)) {
            // This should throw an appropriate validation error.
            $schema->validate($array, $sparse);
        }

        /** @var Validation $validation */
        $validationClass = $schema->getValidationClass();
        if (is_string($validationClass)) {
            $validation = new $validationClass();
        } else {
            $validation = $validationClass;
        }

        foreach ($array as $i => &$row) {
            try {
                $row = $schema->validate($row, $sparse);
            } catch (ValidationException $ex) {
                $validation->merge($ex->getValidation(), $i);
            }
        }

        if ($validation->getErrorCount() > 0) {
            throw new ValidationException($validation);
        }
    }

    /**
     * Compose an array of schemas in order.
     *
     * @param Schema[] $schemas
     * @return Schema
     */
    public static function composeSchemas(...$schemas): Schema
    {
        $accumulator = new Schema();
        foreach ($schemas as $schema) {
            $accumulator = $accumulator->merge($schema);
        }
        return $accumulator;
    }

    /**
     * Flatten a schema using a particular join character between properties.
     *
     * Please note this is slightly lossy.
     * Something will only be marked as required if it is required at every level to the leaf node.
     *
     * @param Schema $schema
     * @param string $joinCharacter
     * @return Schema
     */
    public static function flattenSchema(Schema $schema, string $joinCharacter): Schema
    {
        $newProperties = [];
        $newRequired = [];

        /**
         * Internal recursive utility.
         *
         * @param string $schemaPath
         * @param Schema|array $schemaArray
         * @param bool $isRequired
         * @return void
         */
        $flattenInternal = function (string $schemaPath, $schemaArray, bool $isRequired = false) use (
            &$newProperties,
            &$newRequired,
            $joinCharacter,
            &$flattenInternal
        ) {
            $type = $schemaArray["type"] ?? null;
            $properties = $schemaArray["properties"] ?? null;
            $requiredProperties = $schemaArray["required"] ?? [];

            if ($type === "object" && $properties !== null) {
                foreach ($properties as $propertyName => $propertySchema) {
                    $isPropertyRequired = $isRequired && in_array($propertyName, $requiredProperties);
                    $fullPropertyPath = empty($schemaPath)
                        ? $propertyName
                        : "{$schemaPath}{$joinCharacter}{$propertyName}";
                    $flattenInternal($fullPropertyPath, $propertySchema, $isPropertyRequired);
                }
            } else {
                $newProperties[$schemaPath] = $schemaArray;
                if ($isRequired) {
                    $newRequired[] = $schemaPath;
                }
            }
        };

        $flattenInternal("", $schema, true);
        $newSchema = new Schema([
            "type" => "object",
            "properties" => $newProperties,
            "required" => $newRequired,
        ]);
        return $newSchema;
    }
}
