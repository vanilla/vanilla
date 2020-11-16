<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\SetLiterals;

/**
 * Represents a set value that will perform an inline min/max expression.
 */
class MinMax extends SetLiteral {
    public const OP_MIN = 'min';
    public const OP_MAX = 'max';

    /**
     * @var string
     */
    private $op;

    /**
     * @var mixed
     */
    private $value;

    /**
     * MinMax constructor.
     *
     * @param string $op
     * @param mixed $value
     */
    public function __construct(string $op, $value) {
        if (!in_array($op, [self::OP_MIN, self::OP_MAX], true)) {
            throw new \InvalidArgumentException("Operator must be one of: min, max.", 400);
        }
        $this->op = $op;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string {
        $quoted = $sql->quote($this->value);
        $op = $this->op === self::OP_MIN ? '<' : '>';

        $sql = "case when $escapedFieldName is null or $quoted $op $escapedFieldName then $quoted else $escapedFieldName end";
        return $sql;
    }

    /**
     * Get the operator.
     *
     * @return string
     */
    public function getOp(): string {
        return $this->op;
    }

    /**
     * Get the value.
     *
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }
}
