<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Schema;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Vanilla\Utility\ArrayUtils;

/**
 * A chippy little class that represents a range of values. It supports string parsing and data validation.
 *
 * This class is generally to support range filter parameters in API endpoints. The general use will be:
 *
 * ```
 * $in = new Schema::parse([
 *      'dateInserted' => RangeExpression::createSchema([':date']),
 *      ...
 * ]);
 * ```
 *
 * This adds a range expression validation to an input schema. The value is then usually provided as a string that will
 * parse and validate with the schema. The kicker is that a new `RangeExpression` is created during the validation.
 *
 * It's very cool!
 *
 * Supported range expressions include:
 *
 * - from..to
 * - >2020-10-30
 * - [1,3)
 * - 2020-05-16
 * - a,b,c
 * - An array of values.
 */
class RangeExpression implements \JsonSerializable
{
    public const SCHEMA_DATE = [
        "type" => "datetime",
    ];
    public const SCHEMA_INT = [
        "type" => "integer",
    ];

    private const OPERATORS = ["<", "<=", "=", ">=", ">"];

    protected const BRACKETS = [
        ">=" => "[",
        ">" => "(",
        "<" => ")",
        "<=" => "]",
    ];

    private const REGEX_SINGLE_VALUE = <<<EOT
`
^([<>=!]+)  # Operator
\s*             # Eat whitespace
(.+)$           # value
`
mx
EOT;

    private const REGEX_RANGE = <<<EOT
`
^([[(])?         # Left bracket
([^)\]]*)        # Inner
([)\]])?$        # Right bracket
`
mx
EOT;

    private const REGEX_RANGE_INNER = <<<EOT
`
^\s*([^.,\s]+)?        # From
\s*(?:\.\.\.?|,)\s* # Separator
([^.,\]\)\s]+)?\s*$    # To
`
mx
EOT;

    /**
     * @var array
     */
    protected $values;

    /**
     * @var string
     */
    private $originalString;

    /**
     * RangeFilter constructor.
     *
     * @param string $op Filter operator.
     * @param mixed $value Filter value.
     * @param string|null $op2 Filter operator.
     * @param mixed|null $value2 Filter value.
     */
    public function __construct(string $op, $value, string $op2 = null, $value2 = null)
    {
        $this->addValue($op, $value);
        if ($op2 !== null && $value2 !== null) {
            $this->addValue($op2, $value2);
        }
    }

    /**
     * Parse a range expression
     *
     * @param mixed $expr The expression to parse. This is generally an array of values or a stringable.
     * @param Schema|null $valueSchema A schema to validate individual values.
     * @param bool $keepExpr
     * @return self
     */
    public static function parse($expr, Schema $valueSchema = null, bool $keepExpr = false)
    {
        $validation = new Validation();

        if (in_array($expr, ["", null], true)) {
            throw self::createValidationException("{field} cannot be empty.");
        } elseif (!is_scalar($expr) && !is_array($expr)) {
            throw self::createValidationException("The value is not a valid range expression.");
        }

        if (is_array($expr)) {
            if ($valueSchema !== null) {
                foreach ($expr as $i => &$value) {
                    $value = static::validateValue($value, $valueSchema, $validation, "value[$i]", "=");
                }
            }
            if ($validation->isValid()) {
                return new static("=", array_values($expr));
            }
        } elseif (preg_match(self::REGEX_SINGLE_VALUE, $expr, $m)) {
            // This is a single value expression (ex. '>=10', '<1000')
            [$_, $op, $value] = $m;

            $op = self::translateOp($op);
            $value = static::validateValue($value, $valueSchema, $validation, "", $op);

            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : "", $op, $value);
            }
        } elseif (
            !in_array(substr($expr, 0, 1), ["(", "["], true) &&
            !in_array(substr($expr, -1), [")", "]"], true) &&
            strpos($expr, ",") !== false
        ) {
            // This is a CSV list.
            $values = array_map("trim", explode(",", $expr));
            if ($valueSchema !== null) {
                foreach ($values as $i => &$value) {
                    $value = static::validateValue($value, $valueSchema, $validation, "value[$i]", "=");
                }
            }
            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : "", "=", $values);
            }
        } elseif (preg_match(self::REGEX_RANGE, $expr, $m)) {
            // This is a range expression (ex. '1..10', '(1,5]', '2020-05-01..2020-05-14)')
            [$_, $left, $inner, $right] = $m + array_fill(0, 4, "");
            $parts = preg_split("`,|\.\.\.?`", $inner);
            if (count($parts) === 1 && empty($left) && empty($right)) {
                // This is just a normal value.
                $expr = static::validateValue($expr, $valueSchema, $validation, "", "=");

                if ($validation->isValid()) {
                    return self::creatRangeExpression($keepExpr ? $expr : "", "=", $expr);
                } else {
                    throw new ValidationException($validation);
                }
            } elseif ($parts === false || count($parts) !== 2) {
                throw self::createValidationException("{field} range must contain two values.", ["expr" => $inner]);
            }
            $parts = array_map("trim", $parts);
            [$from, $to] = $parts;

            if ($from === "" && $to === "") {
                throw self::createValidationException("At least one value in the range is required.");
            }

            $args = [];
            if ($from !== "") {
                $op = self::translateOp($left ?: ">=");
                $from = static::validateValue($from, $valueSchema, $validation, "from", $op);
                $args[] = $op;
                $args[] = $from;
            }

            if ($to !== "") {
                $op = self::translateOp($right ?: "<=");
                $to = static::validateValue($to, $valueSchema, $validation, "to", $op);
                $args[] = $op;
                $args[] = $to;
            }

            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : "", ...$args);
            }
        } else {
            // This is just a single value so consider it an equality match.
            $expr = static::validateValue($expr, $valueSchema, $validation, "", "=");

            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : "", "=", $expr);
            }
        }
        throw new ValidationException($validation);
    }

    /**
     * Create a range validation schema.
     *
     * @param Schema $valueSchema
     * @return Schema
     */
    public static function createSchema($valueSchema = null): Schema
    {
        if (is_array($valueSchema)) {
            $valueSchema = Schema::parse($valueSchema);
        } elseif ($valueSchema !== null && !$valueSchema instanceof Schema) {
            throw new \InvalidArgumentException('$valueSchema must be an array or a Schema.', 400);
        }
        $class = static::class;

        $schema = new class (
            [
                "type" => ["string", "integer", "array"],
                "format" => "range-filter",
            ],
            $class,
            $valueSchema
        ) extends Schema {
            /**
             * @var Schema|null
             */
            private $valueSchema;

            /**
             * @var string
             */
            private $class;

            /**
             *  {@inheritDoc}
             */
            public function __construct($schema, string $class, ?Schema $valueSchema = null)
            {
                parent::__construct($schema);
                $this->valueSchema = $valueSchema;
                $this->class = $class;
            }

            /**
             * {@inheritDoc}
             */
            public function validate($data, $sparse = false)
            {
                $r = call_user_func([$this->class, "parse"], $data, $this->valueSchema);
                return $r;
            }
        };

        return $schema;
    }

    /**
     * Add an operator and value.
     *
     * @param string $op
     * @param mixed $value
     * @return $this
     */
    private function addValue(string $op, $value): self
    {
        $op = self::translateOp($op);

        $this->values[$op] = $value;
        return $this;
    }

    /**
     * Validate a value from the range.
     *
     * @param mixed $value The value to validate.
     * @param Schema|null $schema The schema to validate against or **null** not to validate.
     * @param Validation $validation The validation object collecting errors.
     * @param string $name The path of the value to validate.
     * @param string $op The operation being validated against. Some validators might return a different value depending on the operator.
     * @return Invalid|mixed Returns the valid value or invalid.
     */
    protected static function validateValue($value, ?Schema $schema, Validation $validation, string $name, string $op)
    {
        if ($schema === null) {
            return $value;
        } else {
            try {
                return $schema->validate($value);
            } catch (ValidationException $ex) {
                // Kludge to work around small bug in schema where empty names aren't allowed for merging.
                if (empty($name)) {
                    throw $ex;
                }
                $validation->merge($ex->getValidation(), $name);
                return Invalid::value();
            }
        }
    }

    /**
     * Create a single string validation exception.
     *
     * @param string $message
     * @param array $context
     * @return ValidationException
     */
    protected static function createValidationException(string $message, array $context = []): ValidationException
    {
        $validation = new Validation();
        $validation->addError("", $message, $context);
        return new ValidationException($validation);
    }

    /**
     * Get the filter values and operators.
     *
     * @return string[] Returns an associative array with key operators.
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Get the filter value for a single operator.
     *
     * @param string $op The operator to inspect.
     * @return mixed|null Returns the filter value or **null** if there isn't one.
     */
    public function getValue(string $op)
    {
        return $this->values[self::translateOp($op)] ?? null;
    }

    /**
     * Create a range expression and set its original expression.
     *
     * @param string $expr
     * @param mixed $params
     * @return self
     */
    private static function creatRangeExpression(string $expr, ...$params): self
    {
        if ($params[0] === "=" && ($params[1] ?? null) instanceof RangeExpression) {
            $r = $params[1];
        } else {
            $r = new static(...$params);
        }
        if ($expr) {
            $r->originalString = $expr;
        }
        return $r;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * Convert this object to a string.
     *
     * @return string
     */
    public function __toString()
    {
        if (!empty($this->originalString)) {
            return $this->originalString;
        }

        $values = $this->values;
        foreach ($values as $key => &$value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTime::RFC3339);
            }
        }

        if (count($values) === 1) {
            if (isset($values["="])) {
                return is_array($values["="]) ? implode(",", $values["="]) : (string) $values["="];
            } else {
                return key($values) . current($values);
            }
        } elseif (count($values) === 2 && !isset($values["="])) {
            if (isset($values[">="]) && isset($values["<="])) {
                return $values[">="] . ".." . $values["<="];
            } else {
                $left = isset($values[">"]) ? ">" : ">=";
                $right = isset($values["<"]) ? "<" : "<=";
                return self::BRACKETS[$left] . $values[$left] . "," . $values[$right] . self::BRACKETS[$right];
            }
        } else {
            $pairs = [];
            foreach ($values as $key => $val) {
                if (is_array($val)) {
                    $val = implode(",", $val);
                }
                $pairs[] = $key . $val;
            }
            $result = implode(";", $pairs);
            return $result;
        }
    }

    /**
     * Create a new range with a different value.
     *
     * @param string $op The operator to add.
     * @param mixed $value The value at the operator.
     * @return self
     */
    public function withValue(string $op, $value): self
    {
        $range = clone $this;
        $range->originalString = null;
        $range->addValue($op, $value);
        return $range;
    }

    /**
     * Add a value to the range, merging with the existing filter.
     *
     * This method is similar to an AND operation.
     *
     * Example:
     *
     * ```php
     * $range = new Range('>', 5);
     * $range2 = $range->withFilteredValue('>', 6);
     * echo $range2->getValue('>'); // outputs 6
     * ```
     *
     * @param string $op The operator to add.
     * @param mixed $value The new filter value.
     * @return self
     */
    public function withFilteredValue(string $op, $value): self
    {
        $op = self::translateOp($op);
        $range = clone $this;
        $range->originalString = null;

        if (!isset($range->values[$op])) {
            $range->addValue($op, $value);
        } else {
            // If we have a similar op then we need to pick the "stricter" one.
            switch ($op) {
                case ">":
                case ">=":
                    $value = max($value, $range->getValue($op));
                    break;
                case "<":
                case "<=":
                    $value = min($value, $range->getValue($op));
                    break;
                case "=":
                    $value = array_intersect((array) $value, (array) $range->getValue($op));
                    if (count($value) === 1) {
                        $value = array_pop($value);
                    } else {
                        $value = array_values($value);
                    }
                    break;
            }
            $range->addValue($op, $value);
        }
        return $range;
    }

    /**
     * Translate an operator into its canonical form.
     *
     * @param string $op
     * @return string
     */
    private static function translateOp(string $op): string
    {
        if (!in_array($op, self::OPERATORS)) {
            switch ($op) {
                case "[":
                    $op = ">=";
                    break;
                case "]":
                    $op = "<=";
                    break;
                case "(":
                    $op = ">";
                    break;
                case ")":
                    $op = "<";
                    break;
                default:
                    $validation = new Validation();
                    $validation->addError("", "{op} is not a valid operator.", ["op" => $op]);
                    throw new ValidationException($validation);
            }
        }
        return $op;
    }
}
