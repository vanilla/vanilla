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
final class RangeExpression {
    private const OPERATORS = [
        '<', '<=', '=', '>=', '>'
    ];

    private const BRACKETS = [
        '>=' => '[',
        '>' => '(',
        '<' => ')',
        '<=' => ']',
    ];

    private const REGEX_SINGLE_VALUE = <<<EOT
`
^(>=|<=|=|<|>)  # Operator
\s*             # Eat whitespace
(.+)$           # value
`
mx
EOT;

    private const REGEX_RANGE = <<<EOT
`
^([[(])?\s*         # Left bracket
([^.,\s]+)?           # From
\s*(?:\.\.\.?|,)\s* # Separator
([^.,\]\)\s]+)?       # To
\s*([)\]])?            # Right bracket
`
mx
EOT;

    /**
     * @var array
     */
    private $values;

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
    public function __construct(string $op, $value, string $op2 = null, $value2 = null) {
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
     * @return RangeExpression
     */
    public static function parse($expr, Schema $valueSchema = null, bool $keepExpr = false): RangeExpression {
        $validation = new Validation();

        if (!is_scalar($expr) && !is_array($expr)) {
            throw self::createValidationException("The value is not a valid range expression.");
        }

        if (is_array($expr)) {
            if ($valueSchema !== null) {
                foreach ($expr as $i => &$value) {
                    $value = self::validateValue($value, $valueSchema, $validation, "value[$i]");
                }
            }
            if ($validation->isValid()) {
                return new RangeExpression('=', array_values($expr));
            }
        } elseif (preg_match(self::REGEX_SINGLE_VALUE, $expr, $m)) {
            // This is a single value expression (ex. '>=10', '<1000')
            [$_, $op, $value] = $m;

            $value = self::validateValue($value, $valueSchema, $validation, 'value');

            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : '', $op, $value);
            }
        } elseif (!in_array(substr($expr, 0, 1), ['(', '['], true) &&
            !in_array(substr($expr, -1), [')', ']'], true) &&
            strpos($expr, ',') !== false) {
            // This is a CSV list.
            $values = array_map('trim', explode(',', $expr));
            if ($valueSchema !== null) {
                foreach ($values as $i => &$value) {
                    $value = self::validateValue($value, $valueSchema, $validation, "value[$i]");
                }
            }
            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : '', '=', $values);
            }
        } elseif (preg_match(self::REGEX_RANGE, $expr, $m)) {
            // This is a range expression (ex. '1..10', '(1,5]', '2020-05-01..2020-05-14)')
            [$_, $left, $from, $to, $right] = $m + array_fill(0, 5, '');

            if ($from === '' && $to === '') {
                throw self::createValidationException('At least one value in the range is required.');
            }

            $args = [];
            if ($from !== '') {
                $from = self::validateValue($from, $valueSchema, $validation, 'from');
                $args[] = $left ?: '>=';
                $args[] = $from;
            }

            if ($to !== '') {
                $to = self::validateValue($to, $valueSchema, $validation, 'to');
                $args[] = $right ?: '<=';
                $args[] = $to;
            }

            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : '', ...$args);
            }
        } else {
            // This is just a single value so consider it an equality match.
            $expr = self::validateValue($expr, $valueSchema, $validation, 'value');

            if ($validation->isValid()) {
                return self::creatRangeExpression($keepExpr ? $expr : '', '=', $expr);
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
    public static function createSchema($valueSchema = null): Schema {
        if (is_array($valueSchema)) {
            $valueSchema = Schema::parse($valueSchema);
        } elseif ($valueSchema !== null && !$valueSchema instanceof Schema) {
            throw new \InvalidArgumentException('$valueSchema must be an array or a Schema.', 400);
        }

        $schema = new class ([
            'type' => 'string',
            'format' => 'range-filter'
        ], $valueSchema) extends Schema {
            /**
             * @var Schema|null
             */
            private $valueSchema;

            /**
             *  {@inheritDoc}
             */
            public function __construct($schema = [], ?Schema $valueSchema = null) {
                parent::__construct($schema);
                $this->valueSchema = $valueSchema;
            }

            /**
             * {@inheritDoc}
             */
            public function validate($data, $sparse = false) {
                $r = RangeExpression::parse($data, $this->valueSchema);
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
    private function addValue(string $op, $value): self {
        $op = $this->translateOp($op);

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
     * @return Invalid|mixed Returns the valid value or invalid.
     */
    private static function validateValue($value, ?Schema $schema, Validation $validation, string $name) {
        if ($schema === null) {
            return $value;
        } else {
            try {
                return $schema->validate($value);
            } catch (ValidationException $ex) {
                $validation->merge($ex->getValidation(), $name);
                return Invalid::value();
            }
        }
    }

    /**
     * Create a single string validation exception.
     *
     * @param string $message
     * @return ValidationException
     */
    private static function createValidationException(string $message): ValidationException {
        $validation = new Validation();
        $validation->addError('', $message);
        return new ValidationException($validation);
    }

    /**
     * Get the filter values and operators.
     *
     * @return string[] Returns an associative array with key operators.
     */
    public function getValues(): array {
        return $this->values;
    }

    /**
     * Get the filter value for a single operator.
     *
     * @param string $op The operator to inspect.
     * @return mixed|null Returns the filter value or **null** if there isn't one.
     */
    public function getValue(string $op) {
        return $this->values[$this->translateOp($op)] ?? null;
    }

    /**
     * Create a range expression and set its original expression.
     *
     * @param string $expr
     * @param mixed $params
     * @return RangeExpression
     */
    private static function creatRangeExpression(string $expr, ...$params): RangeExpression {
        $r = new RangeExpression(...$params);
        if ($expr) {
            $r->originalString = $expr;
        }
        return $r;
    }

    /**
     * Convert this object to a string.
     *
     * @return string
     */
    public function __toString() {
        if (!empty($this->originalString)) {
            return $this->originalString;
        }

        if (count($this->values) === 1) {
            if (isset($this->values['='])) {
                return is_array($this->values['=']) ? implode(',', $this->values['=']) : (string)$this->values['='];
            } else {
                return key($this->values).current($this->values);
            }
        } elseif (count($this->values) >= 2) {
            if (isset($this->values['>=']) && isset($this->values['<='])) {
                return $this->values['>='].'..'.$this->values['<='];
            } else {
                $left = isset($this->values['>']) ? '>' : '>=';
                $right = isset($this->values['<']) ? '<' : '<=';

                return self::BRACKETS[$left].$this->values[$left].','.$this->values[$right].self::BRACKETS[$right];
            }
        } else {
            // @codeCoverageIgnoreStart
            return '';
            // @codeCoverageIgnoreStop
        }
    }

    /**
     * Create a new range with a different value.
     *
     * @param string $op The operator to add.
     * @param mixed $value The value at the operator.
     * @return RangeExpression
     */
    public function withValue(string $op, $value): RangeExpression {
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
     * @return RangeExpression
     */
    public function withFilteredValue(string $op, $value): RangeExpression {
        $op = $this->translateOp($op);
        $range = clone $this;
        $range->originalString = null;

        if (!isset($range->values[$op])) {
            $range->addValue($op, $value);
        } else {
            // If we have a similar op then we need to pick the "stricter" one.
            switch ($op) {
                case '>':
                case '>=':
                    $value = max($value, $range->getValue($op));
                    break;
                case '<':
                case '<=':
                    $value = min($value, $range->getValue($op));
                    break;
                case '=':
                    $value = array_intersect((array)$value, (array)$range->getValue($op));
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
    private function translateOp(string $op): string {
        if (!in_array($op, self::OPERATORS)) {
            switch ($op) {
                case '[':
                    $op = '>=';
                    break;
                case ']':
                    $op = '<=';
                    break;
                case '(':
                    $op = '>';
                    break;
                case ')':
                    $op = '<';
                    break;
                default:
                    throw new \InvalidArgumentException("Invalid operator: $op", 400);
            }
        }
        return $op;
    }
}
