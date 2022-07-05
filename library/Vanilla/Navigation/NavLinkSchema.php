<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Navigation;

use Garden\Schema\Schema;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\FormOptions;

/**
 * Class NavLinkSchema
 */
class NavLinkSchema extends Schema
{
    /**
     * Configure the class.
     */
    public function __construct()
    {
        parent::__construct([
            "type" => "object",
            "properties" => [
                "name" => [
                    "type" => "string",
                    "minLength" => 1,
                    "x-control" => SchemaForm::textBox(new FormOptions("Name", "Link Name.")),
                ],
                "url" => [
                    "type" => "string",
                    "minLength" => 1,
                    "x-control" => SchemaForm::textBox(new FormOptions("URL", "Link URL.")),
                ],
                "id" => [
                    "type" => "string",
                    "minLength" => 1,
                ],
                "permission" => [
                    "type" => "string",
                    "minLength" => 1,
                ],
                "isHidden" => [
                    "type" => "boolean",
                ],
            ],
            "required" => ["name", "url"],
        ]);
    }
}
