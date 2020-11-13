<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Events;

use Garden\Events\ResourceEvent;
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
}
