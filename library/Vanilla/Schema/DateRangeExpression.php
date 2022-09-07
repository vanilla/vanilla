<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Schema;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\Validation;

/**
 * Class DateRangeExpression
 * @package Vanilla\Schema
 */
class DateRangeExpression extends RangeExpression
{
    /**
     * Ensure that a schema is a date-time schema.
     *
     * @param Schema|null $schema The schema to check.
     * @return Schema Returns the passed schema or a new default date-time schema.
     */
    private static function ensureSchema(?Schema $schema): Schema
    {
        if ($schema !== null) {
            if ($schema->getField("type") !== "datetime") {
                throw new \InvalidArgumentException("DateRangeExpression expects a date value schema.");
            }
        } else {
            $schema = new Schema(self::SCHEMA_DATE);
        }
        return $schema;
    }

    /**
     * {@inheritDoc}
     */
    public static function parse($expr, Schema $valueSchema = null, bool $keepExpr = false)
    {
        $valueSchema = self::ensureSchema($valueSchema);

        $r = parent::parse($expr, $valueSchema, $keepExpr);

        // Make sure the end date is after the start date.
        $values = array_values($r->getValues());
        if (count($values) > 1 && $values[0] > $values[1]) {
            throw self::createValidationException("End of {field} range must come after beginning.");
        }

        return $r;
    }

    /**
     * {@inheritDoc}
     */
    public static function createSchema($valueSchema = null): Schema
    {
        $valueSchema = self::ensureSchema($valueSchema);
        return parent::createSchema($valueSchema);
    }

    /**
     * {@inheritDoc}
     */
    protected static function validateValue($value, ?Schema $schema, Validation $validation, string $name, string $op)
    {
        $schema = self::ensureSchema($schema);
        // First make sure the date is valid.
        $valid = parent::validateValue($value, $schema, $validation, $name, $op);
        if (!$valid instanceof \DateTimeInterface) {
            return Invalid::value();
        }
        $result = new \DateTimeImmutable($valid->format(\DateTime::RFC3339));

        // Handle the special case of whole dates (i.e. dates without times).
        // Whole dates end up having different values depending on the operator they are using.
        if (is_string($value) && self::isWholeDate($value, $valid)) {
            switch ($op) {
                case "(":
                case ">":
                case "<=":
                case "]":
                    $result = $result->modify("+1 day")->modify("-1 second");
                    break;
                case "=":
                    // This is an edge case where testing equality to a whole date actually needs to turn into a range.
                    $to = $result->modify("+1 day")->modify("-1 second");
                    $result = new static(">=", $result, "<=", $to);
                    break;
            }
        }
        return $result;
    }

    /**
     * Determine whether or not a date expression represents a date or date-time.
     *
     * @param string $dateExpr The date expression being tested.
     * @param \DateTimeInterface $date The date that the expression evaluated to.
     * @return bool
     */
    private static function isWholeDate(string $dateExpr, \DateTimeInterface $date): bool
    {
        $time = $date->format("H:i:s");
        $r = $time === "00:00:00" && !preg_match("/\d\d:\d\d(:\d\d)?/", $dateExpr);
        return $r;
    }
}
