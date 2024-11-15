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
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Database\Select;
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

    public const SORT_TRENDING = "experimentalTrending";

    public const SLOT_TYPE_DAY = "d";
    public const SLOT_TYPE_WEEK = "w";
    public const SLOT_TYPE_MONTH = "m";
    public const SLOT_TYPE_YEAR = "y";
    public const SLOT_TYPE_ALL = "a";

    /**
     * Given an array of expand options, determine if a value matches any of them.
     *
     * @param string $value The field name to search for.
     * @param array|bool $options An array of fields to expand, or true for all.
     * @param bool $excludeAll If set to true, then an expand=all or expand=true will not expand this field.
     *
     * @return bool
     */
    public static function isExpandOption(string $value, $options, bool $excludeAll = false): bool
    {
        if (is_array($options) && isset($options["expand"])) {
            $options = $options["expand"];
        }

        if ($value === self::EXPAND_CRAWL) {
            // Specific handling for crawl.
            // It does not match all, or true.
            return is_array($options) && in_array(self::EXPAND_CRAWL, $options);
        }
        $isStartWithMinus = str_starts_with($value, "-");
        $expandAllValues = [self::EXPAND_ALL, "true", true, "1"];
        $validValues = [$value];
        if (!$excludeAll && !$isStartWithMinus) {
            $validValues = array_merge($validValues, $expandAllValues);
        }

        if (is_array($options)) {
            $result = !empty(array_intersect($validValues, $options));
        } else {
            $result = in_array($options, $validValues, true);
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
                if ($currentID !== null && (is_string($currentID) || is_int($currentID))) {
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

    /**
     * Automatically determine a slot type based on a post date.
     *
     * If the post date is less than a day old, it will return "d" for day.
     * If the post date is less than a week old, it will return "w" for week.
     * If the post date is less than a month old, it will return "m" for month.
     * If the post date is less than a year old, it will return "y" for year.
     * If the post date is older than a year, it will return "a" for all.
     *
     * If the determined slot type is not in the valid slot types, it will return the default slot type.
     *
     * @param string|\DateTimeInterface $postDate
     * @return string
     */
    public static function getDateBasedSlotType(
        string|\DateTimeInterface $postDate,
        \DateTimeInterface|string|null $now = null
    ): string {
        $postDate = $postDate instanceof \DateTimeInterface ? $postDate : new \DateTime($postDate);
        $currentTime =
            $now === null
                ? CurrentTimeStamp::getDateTime()
                : ($now instanceof \DateTimeInterface
                    ? $now
                    : new \DateTime($now));
        $diff = $currentTime->diff($postDate);
        $days = $diff->days;
        if ($days < 1) {
            return self::SLOT_TYPE_DAY;
        } elseif ($days < 7) {
            return self::SLOT_TYPE_WEEK;
        } elseif ($days < 31) {
            return self::SLOT_TYPE_MONTH;
        } elseif ($days < 365) {
            return self::SLOT_TYPE_YEAR;
        } else {
            return self::SLOT_TYPE_ALL;
        }
    }

    /**
     * @param string $dateField The date field to filter on.
     * @param string $slotType This will also filter the results to this timeframe. "d" for day, "w" for week, "m" for month.
     * @return CallbackWhereExpression
     */
    public static function slotTypeWhereExpression(string $dateField, string $slotType): CallbackWhereExpression
    {
        return new CallbackWhereExpression(function (\Gdn_MySQLDriver $sql) use ($dateField, $slotType) {
            $currentTime = CurrentTimeStamp::getDateTime();
            $filterTime = null;
            switch ($slotType) {
                case "d":
                    $filterTime = $currentTime->modify("-1 day");
                    break;
                case "w":
                    $filterTime = $currentTime->modify("-1 week");
                    break;
                case "m":
                    $filterTime = $currentTime->modify("-1 month");
                    break;
                case "y":
                    $filterTime = $currentTime->modify("-1 year");
                    break;
                case "a":
                default:
                    break;
            }
            if ($filterTime !== null) {
                $sql->where("$dateField >", $filterTime);
            }
        });
    }

    /**
     * Get a few {@link Select}s for usage in a trending sort. This will create a dynamic field for sorting that is
     * based off of an exponential decay curve and some baseline score calculation.
     *
     * Experimental trending works on the following equation
     * sc = score
     * hc = Hours since creation
     * he = Hour exponent - Make the time decay curve stronger. Size this to your time window.
     * wo = Window offset hours - Offset all posts by a certain amount of the curve. Stronger lessens the effect of the curve over a time period.
     * Our equation is
     *
     * sc / (hc + wo)^he
     *
     * The idea is that we take some aggregate score
     * Then divide by a time factor that makes older posts require a higher score to come to the top
     * Performance of this was tested to be reasonable for discussion datasets of up to ~10k rows
     * Our biggest communities don't tend to make more posts than this in a month.
     * Query time was similar to a DateInserted sort with that many posts.
     *
     * We can't index this easily for bigger datasets because of the time component so at
     * the moment we are relying on the validation to prevent time windows greater than 1 month.
     *
     * @param string $scoreCalculation A SQL expression that calculates a score of a post.
     * @param string $dateField The date field to use for the time decay curve.
     * @param string $slotType A time frame for the exponential decay curve. This will also filter the results to this timeframe. "d" for day, "w" for week, "m" for month.
     *
     * @return array<Select> Returns an array of select statements to add to a query. Notably there will be a select with the value of {@link ModelUtils::SORT_TRENDING} to sort on..
     */
    public static function getTrendingSelects(string $dateField, string $scoreCalculation, string $slotType): array
    {
        switch ($slotType) {
            case "d":
                $windowOffsetHours = 2;
                $hourExponent = 1.5;
                break;
            case "w":
                $windowOffsetHours = 24 * 7;
                $hourExponent = 1.15;
                break;
            case "m":
            default:
                $windowOffsetHours = 24 * 30;
                $hourExponent = 1.1;
                break;
        }

        // We need to add a select for our trending.
        $selects = [
            new Select("@rawTrendingScore := $scoreCalculation", "rawTrendingScore"),
            new Select($windowOffsetHours, "trendingWindowHours"),
            new Select($hourExponent, "trendingWindowExponent"),
            new Select(
                "@hoursSinceCreation := (UNIX_TIMESTAMP() - UNIX_TIMESTAMP($dateField)) / 60 / 24",
                "hoursSinceCreation"
            ),
            new Select(
                "@rawTrendingScore / POWER(@hoursSinceCreation + {$windowOffsetHours}, {$hourExponent})",
                self::SORT_TRENDING
            ),
        ];

        return $selects;
    }
}
