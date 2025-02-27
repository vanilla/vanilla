<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;

/**
 * Abstraction layer to generate common schemas for DiscussionCommentsAsset and DiscussionOriginalPostAsset.
 */
trait PostCommentsAndOriginalPostTrait
{
    /**
     * Get author badges schema (shown in author meta).
     *
     * @return Schema
     */
    public static function authorBadgesSchema(): Schema
    {
        $isBadgesEnabled = \Gdn::addonManager()->isEnabled("badges", \Vanilla\Addon::TYPE_ADDON);
        $schema = $isBadgesEnabled
            ? [
                "authorBadges?" => Schema::parse([
                    "display?" => [
                        "type" => "boolean",
                        "default" => true,
                        "x-control" => SchemaForm::toggle(new FormOptions(t("Show Badges"), "", "")),
                    ],
                    "limit?" => [
                        "type" => "integer",
                        "minimum" => 1,
                        "step" => 1,
                        "maximum" => 5,
                        "default" => 5,
                        "x-control" => SchemaForm::textBox(
                            new FormOptions(
                                t("Badges Limit"),
                                "",
                                "",
                                t(
                                    "Show users' badges on each post. Up to a maximum of 5 badges may be displayed by order of highest ranking."
                                )
                            ),
                            "number",
                            new FieldMatchConditional(
                                "authorBadges.display",
                                Schema::parse([
                                    "type" => "boolean",
                                    "const" => true,
                                ])
                            )
                        ),
                    ],
                ]),
            ]
            : [];

        return Schema::parse($schema);
    }
}
