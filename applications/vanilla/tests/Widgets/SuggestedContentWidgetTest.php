<?php
/**
 * @author David Barbier <dbarbier@higtherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Forum\Widgets\SuggestedContentWidget;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test
 */
class SuggestedContentWidgetTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        \Gdn::config()->saveToConfig("Feature.SuggestedContent.Enabled", true);
        \Gdn::config()->saveToConfig("suggestedContent.enabled", true);
    }

    /**
     * Test widget getProps.
     */
    public function testGetProps()
    {
        $user = $this->createUser();
        $profileField = $this->createProfileField(["dataType" => "boolean", "formType" => "checkbox"]);
        $this->api()->patch("/users/{$user["userID"]}/profile-fields", [
            $profileField["apiName"] => true,
        ]);

        // Create a discussion not in any interests. The purpose of this is to do a negative test.
        // We should only have 1 discussion returned despite 2 created in the trending period.
        $this->createDiscussion();
        $category = $this->createCategory();
        $discussion = $this->createDiscussion();

        $tag = $this->createTag();
        $this->api()->post("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$tag["tagID"]],
        ]);

        // Create interest associated with profile fields.
        $this->createInterest([
            "profileFieldMapping" => [
                $profileField["apiName"] => [true],
            ],
            "tagIDs" => [$tag["tagID"]],
        ]);

        $props = [
            "suggestedFollows" => [
                "enabled" => false,
                "limit" => 5,
            ],
            "suggestedContent" => [
                "enabled" => true,
                "limit" => 5,
                "excerptLength" => 100,
            ],
        ];

        /** @var SuggestedContentWidget $widgetModule */
        $widget = self::container()->get(SuggestedContentWidget::class);
        $widget->setProps($props);

        $widgetResults = $this->runWithUser(function () use ($category, $discussion, $widget) {
            return $widget->getProps();
        }, $user);

        $this->assertCount(1, $widgetResults["discussions"]);
    }
}
