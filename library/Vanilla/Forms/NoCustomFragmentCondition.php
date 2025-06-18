<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

use Garden\Schema\Schema;

/**
 * Condition for a fragment being the system implementation.
 */
class NoCustomFragmentCondition extends FieldMatchConditional
{
    public function __construct(private string $fragmentType)
    {
        parent::__construct("\$fragmentImpls.{$fragmentType}.fragmentUUID", Schema::parse([]));
    }

    public function getCondition(): array
    {
        return [
            "field" => $this->field,
            "type" => "noCustomFragment",
            "fragmentType" => $this->fragmentType,
        ];
    }
}
