<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Events;

use Garden\Events\ResourceEvent;
use Vanilla\Community\Events\CategoryEvent;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Tests for resource events of categories.
 */
class CategoryEventTests extends AbstractAPIv2Test {

    use CommunityApiTestTrait;
    use EventSpyTestTrait;

    /**
     * Tests for simple crud events on the category.
     */
    public function testCategoryCRUDEvents() {
        // Insert
        $category = $this->createCategory([
            'name' => 'Cat 1',
        ]);
        $categoryID = $category['categoryID'];
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'category',
                ResourceEvent::ACTION_INSERT,
                [
                    'name' => 'Cat 1',
                    'categoryID' => $categoryID,
                ]
            )
        );

        // Update
        $this->clearDispatchedEvents();
        $this->api()->patch("/categories/{$categoryID}", [
            'name' => 'Cat 1 updated',
        ]);
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'category',
                ResourceEvent::ACTION_UPDATE,
                [
                    'name' => 'Cat 1 updated',
                    'categoryID' => $categoryID,
                ]
            )
        );

        // Delete
        $this->clearDispatchedEvents();
        $this->api()->delete("/categories/{$categoryID}");
        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'category',
                ResourceEvent::ACTION_DELETE,
                [
                    'name' => 'Cat 1 updated',
                    'categoryID' => $categoryID,
                ]
            )
        );
    }

    /**
     * Test that restricted property updates don't fire events.
     *
     * ie. commentCount, discussionCount.
     */
    public function testRestrictedPropertiesEvents() {
        $category = $this->createCategory([
            'name' => 'Cat 2',
        ]);
        $categoryID = $category['categoryID'];

        $this->clearDispatchedEvents();

        // 1. creating a discussion shouldn't trigger an category update event.

        $discussion = $this->createDiscussion(["categoryID" => $categoryID]);
        $discussionID = $discussion["discussionID"];


        $this->assertEventNotDispatched(["type" => "category", "action" => ResourceEvent::ACTION_UPDATE]);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'discussion',
                ResourceEvent::ACTION_INSERT,
                [
                    'name' => $discussion["name"],
                    'discussionID' => $discussionID,
                ]
            )
        );

        $this->clearDispatchedEvents();

        // 2. creating a comment shouldn't trigger an category update event.
        $comment = $this->createComment(["discussionID" => $discussionID]);

        $this->assertEventNotDispatched(["type" => "category", "action" => ResourceEvent::ACTION_UPDATE]);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'comment',
                ResourceEvent::ACTION_INSERT,
                [
                    'name' => $comment["name"],
                    'commentID' => $comment["commentID"],
                ]
            )
        );

        $this->clearDispatchedEvents();

        // 3. Creating a child category shouldn't trigger an category update event.

        $childCategory = $this->createCategory([
            'name' => 'Child Cat of Cat 2',
            'parentCategoryID' => $categoryID,
        ]);

        $childCategoryID = $childCategory['categoryID'];

        $this->assertEventNotDispatched(["type" => "category", "action" => ResourceEvent::ACTION_UPDATE]);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'category',
                ResourceEvent::ACTION_INSERT,
                [
                    'name' => $childCategory["name"],
                    'categoryID' => $childCategoryID,
                ]
            )
        );

        $this->assertDirtyRecordInserted("category", $categoryID);
    }
}
