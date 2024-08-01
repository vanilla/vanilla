<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use DateTimeImmutable;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationField;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ServerException;
use Vanilla\Schema\DateRangeExpression;
use Vanilla\Schema\LegacyDateRangeExpression;
use Vanilla\Schema\RangeExpression;

/**
 * Validate and parse a date filter string into an easy-to-use array representation.
 */
class DateFilterSchema extends Schema
{
    const DEFAULT_DESCRIPTION =
        "This filter receive a string that can take two forms." .
        "\nA single date that matches '{Operator}{DateTime}' where {Operator} can be =, &lt;, &gt;, &lt;=, &gt;=  and, if omitted, defaults to =." .
        "\nA date range that matches '{Opening}{DateTime},{DateTime}{Closing}' where {Opening} can be '[' or '(' and" .
        " {Closing} can be ']' or ')'. '[]' are inclusive and '()' are exclusive.";
    /** @var array Valid characters for opening an interval-notation range. */
    private $rangeOpen = ["(", "["];

    /** @var array Valid characters for closing an interval-notation range. */
    private $rangeClose = [")", "]"];

    /** @var array Valid operators for simple date comparisons. */
    private $simpleOperators = ["=", ">", "<", ">=", "<="];

    /**
     * @var Schema
     */
    private $adapter;

    /**
     * Initialize an instance of a new DateFilterSchema class.
     *
     * @param array $extra Additional fields to set on the schema.
     */
    public function __construct($extra = [])
    {
        // Use $schema->setDescription() to get rid of the default description.
        if (isset($extra["description"])) {
            $extra["description"] .= "\n" . self::DEFAULT_DESCRIPTION;
        } else {
            $extra["description"] = self::DEFAULT_DESCRIPTION;
        }

        $this->adapter = LegacyDateRangeExpression::createSchema();

        parent::__construct(
            [
                "type" => "object",
                "properties" => [
                    "operator" => [
                        "type" => "string",
                        "default" => "=",
                        "enum" => array_merge($this->simpleOperators, ["[]", "[)", "(]", "()"]),
                    ],
                    "date" => [
                        "type" => "array",
                        "minItems" => 1,
                        "maxItems" => 2,
                        "items" => [
                            "type" => "datetime",
                        ],
                    ],
                    "inclusiveRange" => [
                        "type" => "array",
                        "minItems" => 2,
                        "maxItems" => 2,
                        "items" => [
                            "type" => "datetime",
                        ],
                    ],
                ],
            ] + $extra
        );
    }

    /**
     * Serialize the date filter as structure that Open API understands.
     *
     * @return array Returns the schema array.
     */
    public function jsonSerialize()
    {
        return [
            "type" => "string",
            "format" => "date-filter",
            "description" => $this->getDescription(),
        ];
    }

    /**
     * Parse an interval-notation date range into an array representation.
     *
     * @param string $dates
     * @param string $open
     * @param string $close
     * @param ValidationField $field
     * @return array|string
     */
    private function parseRange($dates, $open, $close, ValidationField $field)
    {
        // Quick sanity check on the values...
        if (!in_array($open, $this->rangeOpen) || !in_array($close, $this->rangeClose)) {
            $field->addError("invalid", ["messageCode" => "Invalid range format in {field}."]);
            return Invalid::value();
        } elseif (!is_string($dates)) {
            $field->addTypeError("string");
            return Invalid::value();
        }

        // This notation only allows two dates, specifically.
        $dateArray = explode(",", $dates);
        if (count($dateArray) != 2) {
            $field->addError("invalid", ["messageCode" => "{field} date filter range must contain 2 datetime values."]);
            return Invalid::value();
        }

        $fakeField = new ValidationField($this->createValidation(), null, null);

        // Convert strings to datetime objects.
        /** @var DateTimeImmutable[] $dateTimes */
        $dateTimes = [];
        $dateTimes[] = $this->validateDatetime($dateArray[0], $fakeField);
        $dateTimes[] = $this->validateDatetime($dateArray[1], $fakeField);

        if (Invalid::isInvalid($dateTimes[0]) || Invalid::isInvalid($dateTimes[1])) {
            $field->addError("invalid", ["messageCode" => "Both values in {field} must be datetime."]);
            return Invalid::value();
        }

        // Make sure the ending date isn't greater-than or equal-to the beginning date.
        if ($dateTimes[0] >= $dateTimes[1]) {
            $field->addError("invalid", ["messageCode" => "End of {field} range must come after beginning."]);
            return Invalid::value();
        }

        // Adjust the beginning of the range to account for exclusive specifications.
        if ($open == "(") {
            if (preg_match("/\d\d:\d\d:\d\d/", $dateArray[0])) {
                $dateTimes[0] = $dateTimes[0]->modify("+1 second");
            } else {
                $dateTimes[0] = $dateTimes[0]->modify("+1 day");
            }
        }

        // Adjust the closing of the range to account for inclusive and exclusive specifications.
        if ($close == "]" && !preg_match("/\d\d:\d\d:\d\d/", $dateArray[1])) {
            $dateTimes[1] = $dateTimes[1]->modify("+1 day")->modify("-1 second");
        } elseif ($close == ")") {
            $dateTimes[1] = $dateTimes[1]->modify("-1 second");
        }

        $result = [
            "operator" => $open . $close,
            "date" => $dateTimes,
            "inclusiveRange" => $dateTimes,
        ];
        return $result;
    }

    /**
     * Parse a simple date comparison string into an array representation.
     *
     * @param string $date
     * @param string $operator
     * @param ValidationField $field
     * @return array|string
     */
    private function parseSimple($date, $operator, ValidationField $field)
    {
        if ($operator == "") {
            $operator = "=";
        }

        // Sanity check on the parameters...
        if (!is_string($date) || !in_array($operator, $this->simpleOperators)) {
            $field->addError("invalid", ["messageCode" => "Invalid operator in {field}."]);
            return Invalid::value();
        }

        if (Invalid::isInvalid($this->validateDatetime($date, $field))) {
            return Invalid::value();
        }

        try {
            $dateTime = new DateTimeImmutable($date);
        } catch (\Exception $e) {
            $field->addTypeError("datetime");
            return Invalid::value();
        }

        $dateTimes = [$dateTime];
        $inclusiveRange = [];

        // If all we have is a date, give us a range in that date.
        if (!preg_match("/\d\d:\d\d:\d\d/", $date)) {
            switch ($operator) {
                case "=":
                    $dateTimes = [$dateTimes[0], $dateTimes[0]->modify("+1 day")->modify("-1 second")];
                    $inclusiveRange = $dateTimes;
                    break;
                case "<=":
                    $dateTimes = [self::currentDayEnd($dateTime)];
                    $inclusiveRange = [self::farPastDate(), self::currentDayEnd($dateTime)];
                    break;
                case "<":
                    $inclusiveRange = [self::farPastDate(), self::prevDayEnd($dateTime)];
                    break;
                case ">=":
                    $inclusiveRange = [self::currentDayStart($dateTime), self::farFutureDate()];
                    break;
                case ">":
                    $inclusiveRange = [self::nextDayStart($dateTime), self::farFutureDate()];
            }
        } else {
            switch ($operator) {
                case "=":
                    // Very super specific range here.
                    $inclusiveRange = [$dateTime, $dateTime];
                    break;
                case "<=":
                    $inclusiveRange = [self::farPastDate(), $dateTime];
                    break;
                case "<":
                    $inclusiveRange = [self::farPastDate(), $dateTime->modify("-1 second")];
                    break;
                case ">=":
                    $inclusiveRange = [$dateTime, self::farFutureDate()];
                    break;
                case ">":
                    $inclusiveRange = [$dateTime->modify("+1 second"), self::farFutureDate()];
            }
        }

        $result = [
            "operator" => $operator,
            "date" => $dateTimes,
            "inclusiveRange" => $inclusiveRange,
        ];
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($data, $sparse = false)
    {
        if ($data instanceof DateRangeExpression) {
            return $data;
        } elseif (is_scalar($data) || is_null($data)) {
            $valid = $this->adapter->validate($data, $sparse);
            return $valid;
        }

        $validation = $this->createValidation();
        $field = new ValidationField($validation, $this->getSchemaArray(), "", $sparse);

        if (is_string($data)) {
            $data = $this->stringToSchema($data, $field);
        }

        if (Invalid::isValid($data)) {
            if (is_array($data)) {
                $clean = $this->validateField($data, $field, $sparse);

                if (Invalid::isInvalid($clean) && $field->isValid()) {
                    // This really shouldn't happen, but we want to protect against seeing the invalid object.
                    $field->addError("invalid", ["messageCode" => "{field} is invalid.", "status" => 422]);
                }
            } else {
                $field->addError("invalid", ["messageCode" => "{field} is not a valid date filter."]);
            }
        }

        if (!$validation->isValid()) {
            throw new ValidationException($field->getValidation());
        }
        // Convert the old-school range array into a RangeExpression
        $r = LegacyDateRangeExpression::createFromLegacyArray($clean);
        return $r;
    }

    /**
     * Validate a date filter.
     *
     * @param mixed $value The value to validate.
     * @param ValidationField $field The validation results to add.
     * @return string|false Returns the valid string or **null** if validation fails.
     */
    private function stringToSchema($value, ValidationField $field)
    {
        $result = Invalid::value();

        $escapedOpen = "[" . implode("", array_map("preg_quote", $this->rangeOpen)) . "]";
        $escapedClose = "[" . implode("", array_map("preg_quote", $this->rangeClose)) . "]";

        // Sort the operators so that the matches occur on the longest operators first.
        $sortedSimpleOperators = $this->simpleOperators;
        usort($sortedSimpleOperators, function ($a, $b) {
            if (strlen($a) > strlen($b)) {
                return -1;
            } elseif (strlen($a) < strlen($b)) {
                return 1;
            }
            return 0;
        });
        $simpleOperators = "(?<op>" . implode("|", array_map("preg_quote", $sortedSimpleOperators)) . "|)";

        if (
            preg_match("/^(?<open>" . $escapedOpen . ")(?<date>.+?)(?<close>" . $escapedClose . ')$/', $value, $matches)
        ) {
            $result = $this->parseRange($matches["date"], $matches["open"], $matches["close"], $field);
        } elseif (preg_match("/^(?!$escapedOpen)$simpleOperators(?<date>.+)/", $value, $matches)) {
            $result = $this->parseSimple($matches["date"], $matches["op"], $field);
        } else {
            $field->addError("invalid", ["messageCode" => "{field} is not formatted as a valid date filter."]);
        }

        return $result;
    }

    /**
     * If the parameter value is a valid date filter value, return an array of query conditions.
     *
     * @param string $field The name of the field in the filters.
     * @param mixed $dateData The decoded date data.
     * @return array
     * @throws \InvalidArgumentException Throws an exception when the operator is invalid.
     * @deprecated Use the `DateRangeExpression` class. It can be passed directly to queries.
     */
    public static function dateFilterField($field, $dateData)
    {
        $validOperators = ["=", ">", "<", ">=", "<=", "[]", "()", "[)", "(]"];
        $result = [];

        if (!empty($dateData["operator"]) && !empty($dateData["date"]) && is_array($dateData["date"])) {
            $op = $dateData["operator"];
            $dates = $dateData["date"];

            if (in_array($op, $validOperators)) {
                switch ($op) {
                    case ">":
                    case "<":
                    case ">=":
                    case "<=":
                        if ($dates[0] instanceof DateTimeImmutable) {
                            $result = ["{$field} {$op}" => $dates[0]];
                        }
                        break;
                    case "[]":
                    case "()":
                    case "[)":
                    case "(]":
                        // DateFilterSchema has already taken care of any inclusive/exclusive range adjustments
                        // so we can always use >= and <=
                        $result = [
                            "{$field} >=" => $dates[0],
                            "{$field} <=" => $dates[1],
                        ];
                        break;
                    case "=":
                        if (count($dates) === 1) {
                            $result = ["{$field}" => $dates[0]];
                        } else {
                            $result = [
                                "{$field} >=" => $dates[0],
                                "{$field} <=" => $dates[1],
                            ];
                        }
                        break;
                }
            }
        } else {
            throw new \InvalidArgumentException("Invalid data supplied to dateFilterField");
        }

        return $result;
    }

    // Some small utiltiies

    /**
     * Get the furthest possible future date PHP can contain.
     *
     * @return DateTimeImmutable
     */
    public static function farFutureDate(): DateTimeImmutable
    {
        return new DateTimeImmutable("Jan 1 2300");
    }

    /**
     * Get the furthest possible past date PHP can contain.
     *
     * @return DateTimeImmutable
     */
    public static function farPastDate(): DateTimeImmutable
    {
        return new DateTimeImmutable("@0");
    }

    /**
     * Adjust the date to the first second of the next day.
     *
     * @param DateTimeImmutable $date
     * @return DateTimeImmutable
     */
    private static function nextDayStart(DateTimeImmutable $date): DateTimeImmutable
    {
        return self::currentDayStart($date)->modify("+1 day");
    }

    /**
     * Adjust the date to the last second of the previous day.
     *
     * @param DateTimeImmutable $date
     * @return DateTimeImmutable
     */
    private static function prevDayEnd(DateTimeImmutable $date): DateTimeImmutable
    {
        return self::currentDayEnd($date)->modify("-1 day");
    }

    /**
     * Adjust the date to the first second of the day.
     *
     * @param DateTimeImmutable $date
     * @return DateTimeImmutable
     */
    private static function currentDayStart(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime(0, 0, 0);
    }

    /**
     * Adjust the date to the last second of the day.
     *
     * @param DateTimeImmutable $date
     * @return DateTimeImmutable
     */
    private static function currentDayEnd(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime(23, 59, 59);
    }
}
