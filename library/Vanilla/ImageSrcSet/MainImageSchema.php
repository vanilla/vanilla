<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ImageSrcSet;

use Garden\Schema\Schema;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Schema for a primary image in some content.
 */
class MainImageSchema extends Schema
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct([
            "type" => "object",
            "properties" => [
                "url" => [
                    "type" => "string",
                ],
                "urlSrcSet" => new InstanceValidatorSchema(ImageSrcSet::class),
                "alt" => [
                    "type" => "string",
                    "default" => t("Untitled"),
                ],
            ],
        ]);
    }
}
