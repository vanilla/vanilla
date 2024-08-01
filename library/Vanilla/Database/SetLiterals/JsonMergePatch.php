<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Database\SetLiterals;

class JsonMergePatch extends SetLiteral
{
    private array $value;

    /**
     * @param array $value The array to be json-encoded and merged.
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string
    {
        $jsonEncodedValue = $sql->quote(json_encode($this->value));
        return "JSON_MERGE_PATCH($escapedFieldName, CAST($jsonEncodedValue as JSON))";
    }
}
