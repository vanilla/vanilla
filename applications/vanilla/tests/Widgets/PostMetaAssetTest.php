<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Widgets;

use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

class PostMetaAssetTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use LayoutTestTrait;

    /**
     * Test hydrating the post meta asset.
     *
     * @return void
     */
    public function testHydratePostMetaAsset()
    {
        $this->runWithConfig(
            [FeatureFlagHelper::featureConfigKey(PostTypeModel::FEATURE_POST_TYPES) => true],
            function () {
                // Create a post type and some post fields
                $postType = $this->createPostType();
                $postField1 = $this->createPostField([
                    "postFieldID" => "NameInPascalCase",
                    "postTypeID" => $postType["postTypeID"],
                ]);
                $postField2 = $this->createPostField([
                    "postFieldID" => "name-in-kebab-case",
                    "postTypeID" => $postType["postTypeID"],
                ]);

                // Add discussion
                $discussion = $this->createDiscussion([
                    "postTypeID" => $postType["postTypeID"],
                    "postMeta" => [$postField1["postFieldID"] => "abcd", $postField2["postFieldID"] => "efgh"],
                ]);

                $spec = [
                    '$hydrate' => "react.asset.postMeta",
                    "title" => "My Post With Post Fields",
                    "titleType" => "static",
                    "descriptionType" => "none",
                    '$reactTestID' => "postMeta",
                ];

                $expected = [
                    '$reactComponent' => "PostMetaAsset",
                    '$reactProps' => [
                        "title" => "My Post With Post Fields",
                        "postFields" => [
                            self::markForSparseComparision([
                                "postFieldID" => $postField2["postFieldID"],
                                "value" => "efgh",
                            ]),
                            self::markForSparseComparision([
                                "postFieldID" => $postField1["postFieldID"],
                                "value" => "abcd",
                            ]),
                        ],
                    ],
                    '$reactTestID' => "postMeta",
                ];

                $this->assertHydratesTo(
                    $spec,
                    ["discussionID" => $discussion["discussionID"]],
                    $expected,
                    "discussion"
                );
            }
        );
    }
}
