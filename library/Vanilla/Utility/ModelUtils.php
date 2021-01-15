<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Gdn_Locale as LocaleInterface;
use Gdn_Validation;

/**
 * Class ModelUtils.
 */
class ModelUtils {

    // Expand field value to indicate expanding all fields.
    public const EXPAND_ALL = "all";

    // Expand field value to indicate expanding to a crawlable record.
    public const EXPAND_CRAWL = "crawl";

    /**
     * Given an array of expand options, determine if a value matches any of them.
     *
     * @param string $value The field name to search for.
     * @param array|bool $options An array of fields to expand, or true for all.
     * @return bool
     */
    public static function isExpandOption(string $value, $options): bool {
        if ($value === self::EXPAND_CRAWL) {
            // Specific handling for crawl.
            // It does not match all, or true.
            return is_array($options) && in_array(self::EXPAND_CRAWL, $options);
        }

        $result = false;
        if ($options === true) {
            // A boolean true allows everything.
            $result = true;
        } elseif (is_array($options)) {
            $result = !empty(array_intersect([self::EXPAND_ALL, 'true', '1', $value], $options));
        }
        return $result;
    }

    /**
     * Determine the fields that were chosen by expand options.
     *
     * @param string|string[] $fields The expand fields you want to determine.
     * @param string[]|true $options The expand options that were passed to the API.
     * @return array Returns an array containing the items from `$fields` that were selected.
     */
    public static function expandedFields($fields, $options): array {
        $fields = (array)$fields;
        if ($options === true) {
            return $fields;
        }
        $result = [];
        foreach ($fields as $field) {
            if (self::isExpandOption($field, $options)) {
                $result[] = $field;
            }
        }
        return $result;
    }

    /**
     * Convert a Garden Schema validation exception into a Gdn_Validation instance.
     *
     * @param ValidationException $exception
     */
    public static function validationExceptionToValidationResult(ValidationException $exception): Gdn_Validation {
        $result = new Gdn_Validation();
        $errors = $exception->getValidation()->getErrors();

        foreach ($errors as $error) {
            $fieldName = $error["field"] ?? null;
            $message = $error["message"] ?? null;
            if ($fieldName && $message) {
                $errorCode = str_replace($fieldName, "%s", $message);
                $result->addValidationResult($fieldName, $errorCode);
            }
        }

        return $result;
    }

    /**
     * Given a model (old Gdn_Model mainly), analyze its validation property and return failures.
     *
     * @param \Gdn_Model|Gdn_Validation|\Gdn_Form|object $model The model to analyze the Validation property of.
     * @param LocaleInterface|null $locale
     * @param bool $throw If errors are found, should an exception be thrown?
     *
     * @return Validation
     * @throws ValidationException Throws the exception if `$throw` is true.
     */
    public static function validationResultToValidationException($model, LocaleInterface $locale = null, $throw = true) {
        if ($locale === null) {
            $locale = \Gdn::locale();
        }
        $validation = new Validation();
        $caseScheme = new CamelCaseScheme();
        $t = $locale !== null ? [$locale, 'translate'] : function ($str) {
            return $str;
        };

        if (property_exists($model, 'Validation') && $model->Validation instanceof Gdn_Validation) {
            $results = $model->Validation->results();
        } elseif ($model instanceof \Gdn_Validation) {
            $results = $model->results();
        } elseif ($model instanceof \Gdn_Form) {
            $results = $model->validationResults();
        }

        if (isset($results)) {
            $results = $caseScheme->convertArrayKeys($results);
            foreach ($results as $field => $errors) {
                foreach ($errors as $error) {
                    $message = trim(sprintf(
                        $t($error),
                        $t($field)
                    ), '.').'.';
                    $validation->addError(
                        $field,
                        $error,
                        ['message' => $message]
                    );
                }
            }
        }

        if ($throw && $validation->getErrorCount() > 0) {
            throw new ValidationException($validation);
        }

        return $validation;
    }

    /**
     * Join fragments to a dataset.
     *
     * This method is essentially an in code left join. It is a fairly straightforward helper that defers most of the
     * heavy lifting to the `$fetch` parameter. This is a callable that must have the following signature:
     *
     * ```
     * function (array<T> $ids): array<T, mixed>
     * ```
     *
     * The function must take an array of IDs and then return an array that maps those IDs to the records to join.
     *
     * @param array[] $dataset The dataset to join the records to.
     * @param array $idFields The names of the fields to lookup. See `parseJoinFragmentFields()` for more information.
     * @param callable $fetch The fetcher function to call that maps IDs to records.
     * @param mixed $default The default value to set when the the join record is not found.
     */
    public static function leftJoin(array &$dataset, array $idFields, callable $fetch, $default = null): void {
        $idFields = self::parseJoinFragmentFields($idFields);

        // Gather all of the IDs.
        $ids = [];
        foreach ($dataset as $row) {
            foreach ($idFields as $idField => $aliasField) {
                $currentID = ArrayUtils::getByPath($idField, $row);
                $ids[$currentID] = $currentID;
            }
        }

        // Fetch them.
        $fragments = $fetch($ids);

        // Join them back into the data.
        foreach ($dataset as &$row) {
            foreach ($idFields as $idField => $aliasField) {
                ArrayUtils::setByPath($aliasField, $row, $fragments[ArrayUtils::getByPath($idField, $row)] ?? $default);
            }
        }
    }

    /**
     * Take a more freeform join array and and transform it into an array in the form:
     *
     * ```
     * [
     *    "idField1" => "aliasField1",
     *    "idField2" => "aliasField2",
     *    ...
     * ]
     * ```
     *
     * The `idField` is the name of the array key in a dataset used to gather the IDs and the `aliasField` is what is meant
     * to add to the result array. The input array can handle shorter forms, such as.
     *
     * ```
     * [
     *    'insertUserID',
     *    'updateUser',
     *    'roleID' => 'roles'
     * ]
     * ```
     *
     * If you give a single string the function checks to see if it ends in `'ID'` to determine whether it represents
     * the alias or the ID field. The previous example would then result in:
     *
     *  ```
     * [
     *    'insertUserID' => 'insertUser',
     *    'updateUserID' => 'updateDate',
     *    'roleID' => 'roles'
     * ]
     * ```
     *
     * @param array $idFields
     * @return array
     * @see joinFragments
     */
    public static function parseJoinFragmentFields(array $idFields): array {
        $result = [];
        foreach ($idFields as $idField => $aliasField) {
            if (!is_numeric($idField)) {
                $result[$idField] = $aliasField;
            } elseif (substr($aliasField, -2) === 'ID') {
                $result[$aliasField] = substr($aliasField, 0, -2);
            } else {
                $result[$aliasField . 'ID'] = $aliasField;
            }
        }
        return $result;
    }
}
