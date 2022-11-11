<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Gdn_Locale as LocaleInterface;
use Gdn_Validation;
use Iterator;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\UserFragment;
use Vanilla\Scheduler\LongRunner;

/**
 * Class ModelUtils.
 */
class ModelUtils
{
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
    public static function isExpandOption(string $value, $options): bool
    {
        if ($value === self::EXPAND_CRAWL) {
            // Specific handling for crawl.
            // It does not match all, or true.
            return is_array($options) && in_array(self::EXPAND_CRAWL, $options);
        }
        $result = false;
        $isStartWithMinus = str_starts_with($value, "-");
        if ($options === true) {
            // A boolean true allows everything except when starting with "-"
            $result = $isStartWithMinus ? false : true;
        } elseif (is_array($options)) {
            $result = !empty(array_intersect([self::EXPAND_ALL, "true", "1", $value], $options));
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
    public static function expandedFields($fields, $options): array
    {
        $fields = (array) $fields;
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
    public static function validationExceptionToValidationResult(ValidationException $exception): Gdn_Validation
    {
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
    public static function validationResultToValidationException($model, LocaleInterface $locale = null, $throw = true)
    {
        if ($locale === null) {
            $locale = \Gdn::locale();
        }
        $validation = new Validation();
        $caseScheme = new CamelCaseScheme();
        $t =
            $locale !== null
                ? [$locale, "translate"]
                : function ($str) {
                    return $str;
                };

        if (property_exists($model, "Validation") && $model->Validation instanceof Gdn_Validation) {
            $results = $model->Validation->results();
            $model->Validation->reset();
        } elseif ($model instanceof \Gdn_Validation) {
            $results = $model->results();
            $model->reset();
        } elseif ($model instanceof \Gdn_Form) {
            $results = $model->validationResults();
        }

        if (isset($results)) {
            $results = $caseScheme->convertArrayKeys($results);
            foreach ($results as $field => $errors) {
                foreach ($errors as $error) {
                    $message = trim(sprintf($t($error), $t($field)), ".") . ".";
                    $validation->addError($field, $error, ["message" => $message]);
                }
            }
        }

        if ($throw && $validation->getErrorCount() > 0) {
            throw new ValidationException($validation);
        }

        return $validation;
    }

    /**
     * Look at the result of a save and see if it gave some premoderation result.
     *
     * @param string|int|bool $saveResult The result of calling Gdn_Model::save().
     * @param string $recordType The record being validated.
     *
     * @throws ClientException For spam or unapproved content.
     */
    public static function validateSaveResultPremoderation($saveResult, string $recordType)
    {
        if ($saveResult === SPAM || $saveResult === UNAPPROVED) {
            // "Your discussion will appear after it is approved."
            // "Your comment will appear after it is approved."
            $message = t(sprintf("Your %s will appear after it is approved.", strtolower($recordType)));
            throw new ClientException($message, 202);
        }
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
     * @param \Traversable|array[] $dataset The dataset to join the records to.
     * @param array $idFields The names of the fields to look up. See `parseJoinFragmentFields()` for more information.
     * @param callable $fetch The fetcher function to call that maps IDs to records.
     * @param mixed $default The default value to set when the join record is not found.
     */
    public static function leftJoin(&$dataset, array $idFields, callable $fetch, $default = null): void
    {
        $idFields = self::parseJoinFragmentFields($idFields);

        reset($dataset);
        $isSingleRow = is_array($dataset) && is_string(key($dataset));

        // Gather all the IDs.
        $ids = [];
        $extractIDs = function (&$row) use ($idFields, &$ids) {
            foreach ($idFields as $idField => $aliasField) {
                $currentID = ArrayUtils::getByPath($idField, $row);
                if ($currentID !== null) {
                    $ids[$currentID] = $currentID;
                    continue;
                }

                // Some old tests would use the alias field directly as an id. See if that works.
                // At this point the id field wasn't there. Maybe the alias field is an ID?
                $currentID = ArrayUtils::getByPath($aliasField, $row);
                if ($currentID !== null && (is_string($aliasField) || is_int($aliasField))) {
                    $ids[$currentID] = $currentID;
                    // Kludge it into the row.
                    $row[$idField] = $currentID;
                }
            }
        };

        if ($isSingleRow) {
            $extractIDs($dataset);
        } else {
            foreach ($dataset as &$row) {
                $extractIDs($row);
            }
        }

        // Load data for the IDs.
        $fragments = $fetch($ids);

        // Join them back into the data.
        $joinSingleRow = function (&$row) use (&$fragments, &$idFields, &$default) {
            foreach ($idFields as $idField => $aliasField) {
                $fieldExists = arrayPathExists(explode(".", $idField), $row);
                if (!$fieldExists) {
                    continue;
                }
                $newValue = $fragments[ArrayUtils::getByPath($idField, $row)] ?? $default;
                $existingValue = ArrayUtils::getByPath($aliasField, $row, []);
                if ($newValue instanceof UserFragment) {
                    if ($existingValue instanceof UserFragment) {
                        $existingValue = $existingValue->jsonSerialize();
                    }
                    if (is_array($existingValue)) {
                        $newValue->addExtraData($existingValue);
                    }
                }
                ArrayUtils::setByPath($aliasField, $row, $newValue);
            }
        };

        if ($isSingleRow) {
            $joinSingleRow($dataset);
        } else {
            foreach ($dataset as &$row) {
                $joinSingleRow($row);
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
    public static function parseJoinFragmentFields(array $idFields): array
    {
        $result = [];
        foreach ($idFields as $idField => $aliasField) {
            if (!is_numeric($idField)) {
                $result[$idField] = $aliasField;
            } elseif (substr($aliasField, -2) === "ID") {
                $result[$aliasField] = substr($aliasField, 0, -2);
            } else {
                $result[$aliasField . "ID"] = $aliasField;
            }
        }
        return $result;
    }

    /**
     * Iterate through a generator until completed until a specified timeout has been reached.
     *
     * @param Iterator $iterable
     * @param int $timeout
     * @return \Generator<mixed, mixed, bool> A generator yield values of the underlying iterator.
     * The generator will return true if it has completed or false if it has not.
     */
    public static function iterateWithTimeout(Iterator $iterable, int $timeout): \Generator
    {
        $horizon = CurrentTimeStamp::get() + $timeout;

        $memoryLimit = ini_get("memory_limit");
        $memoryLimit = $memoryLimit == -1 ? null : StringUtils::unformatSize($memoryLimit);

        foreach ($iterable as $key => $value) {
            yield $key => $value;
            $memoryExceeded = $memoryLimit ? memory_get_usage() / $memoryLimit > 0.8 : false;
            $newTimestamp = CurrentTimeStamp::get();
            if ($newTimestamp >= $horizon || $memoryExceeded) {
                return false;
            }
        }

        return true;
    }

    /**
     * Consume a generator entirely.
     *
     * @param \Generator $generator
     *
     * @return mixed|null
     */
    public static function consumeGenerator(\Generator $generator)
    {
        while ($generator->valid()) {
            $generator->next();
        }
        $return = $generator->getReturn();
        return $return;
    }
}
