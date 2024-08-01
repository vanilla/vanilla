<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Schema;

use Vanilla\DateFilterSchema;

/**
 * A date range method with specific backwards compatibility for the `DateFilterSchema` class.
 */
final class LegacyDateRangeExpression extends DateRangeExpression implements \ArrayAccess
{
    /**
     * Make a date range inclusive instead of exclusive.
     *
     * @param \DateTimeImmutable[] $values
     * @return array Returns an array in the form `[$from, $to]`.
     */
    private static function makeInclusiveRange(array $values)
    {
        $r = ["from" => DateFilterSchema::farPastDate(), "to" => DateFilterSchema::farFutureDate()];
        foreach ($values as $op => $date) {
            switch ($op) {
                case ">=":
                    $r["from"] = $date;
                    break;
                case ">":
                    $r["from"] = $date->modify("+1 second");
                    break;
                case "<":
                    $r["to"] = $date->modify("-1 second");
                    break;
                case "<=":
                    $r["to"] = $date;
                    break;
                case "=":
                    $r["from"] = $r["to"] = $date;
                    break;
            }
        }
        return array_values($r);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        $arr = $this->toLegacyArray();
        return isset($arr[$offset]);
    }

    /**
     * Create a new instance from a legacy arrya returned by the DateFilterSchema.
     *
     * @param array $arr
     * @return LegacyDateRangeExpression
     */
    public static function createFromLegacyArray(array $arr): LegacyDateRangeExpression
    {
        $r = new static("=", 0);
        $r->fromLegacyArray($arr);

        return $r;
    }

    /**
     * Create a date range from a legacy `DateFilterSchema` validation result.
     *
     * @param array $arr
     */
    public function fromLegacyArray(array $arr): void
    {
        if ($arr["operator"] === "=") {
            $this->values = ["=" => reset($arr["date"])];
        } else {
            $brackets = array_flip(self::BRACKETS);
            if (preg_match("`[(\[][)\]]`", $arr["operator"])) {
                $ops = str_split($arr["operator"]);
            } else {
                $ops = (array) $arr["operator"];
            }
            $values = [];
            foreach ($ops as $i => $op) {
                // Legacy arrays have an annoyance where the date range is always an inclusive array, regardless of the operator.
                // We need to correct for that by adding/subtracting a second.
                /** @var \DateTimeImmutable $date */
                $date = $arr["date"][$i];
                switch ($op) {
                    case "(":
                        $date = $date->modify("-1 second");
                        break;
                    case ")":
                        $date = $date->modify("+1 second");
                        break;
                }
                if (isset(self::BRACKETS[$op])) {
                    $values[$op] = $date;
                } else {
                    $values[$brackets[$op]] = $date;
                }
            }
            $this->values = $values;
        }
    }

    /**
     * Convert a date range to a legacy `DateFilterSchema` validation result.
     *
     * @return array
     */
    public function toLegacyArray(): array
    {
        $arr = [];

        if (count($this->values) === 1) {
            $arr["operator"] = key($this->values);
            $arr["date"] = [reset($this->values)];
            $arr["inclusiveRange"] = self::makeInclusiveRange($this->values);

            return $arr;
        }
        $arr["operator"] = "";
        foreach (self::BRACKETS as $op => $bracket) {
            if (isset($this->values[$op])) {
                $arr["operator"] .= $bracket;
            }
        }
        $arr["date"] = $arr["inclusiveRange"] = self::makeInclusiveRange($this->values);
        return $arr;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        $arr = $this->toLegacyArray();
        return $arr[$offset];
    }

    /**
     * {@inheritDoc}
     * @codeCoverageIgnore
     */
    public function offsetSet($offset, $value)
    {
        $arr = $this->toLegacyArray();
        $arr[$offset] = $value;
        $this->fromLegacyArray($arr);
    }

    /**
     * {@inheritDoc}
     * @codeCoverageIgnore
     */
    public function offsetUnset($offset)
    {
        $arr = $this->toLegacyArray();
        unset($arr[$offset]);
        $this->fromLegacyArray($arr);
    }
}
