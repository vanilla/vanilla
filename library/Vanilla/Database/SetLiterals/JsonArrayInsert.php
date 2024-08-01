<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\SetLiterals;

/**
 * MySQL set literal to push into a property of a JSON column.
 */
class JsonArrayInsert extends SetLiteral
{
    private string $pushInto;
    private $value;

    /**
     * @param string $pushInto The JSON subfield to push into.
     * @param mixed $value The JSON value to push.
     */
    public function __construct(string $pushInto, $value)
    {
        $this->value = $value;
        $this->pushInto = $pushInto;
    }

    /**
     * @inheritDoc
     */
    public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string
    {
        // Double encode to escape quotes properly.
        $jsonEncoded = json_encode(json_encode($this->value, JSON_THROW_ON_ERROR));
        return <<<SQL
JSON_ARRAY_INSERT({$escapedFieldName}, "$.{$this->pushInto}[last]", CAST({$jsonEncoded} as JSON))
SQL;
    }
}
