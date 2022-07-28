<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Schemas;

use Garden\Schema\Schema;

/**
 * Schema for minimal category fields.
 */
class CategoryFragmentSchema extends Schema
{
    /**
     * Setup new schema.
     */
    public function __construct()
    {
        parent::__construct(
            $this->parseInternal([
                "categoryID:i" => "The ID of the category.",
                "name:s" => "The name of the category.",
                "url:s" => "Full URL to the category.",
                "allowedDiscussionTypes:a?" => "An array of discussion types allowed on the category.",
            ])
        );
    }

    /**
     * @return string[]
     */
    public static function fieldNames(): array
    {
        return ["categoryID", "name", "url", "allowedDiscussionTypes"];
    }
}
