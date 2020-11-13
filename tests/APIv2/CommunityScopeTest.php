<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Utility\ModelUtils;
use VanillaTests\Cloud\ElasticSearch\ElasticTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Tests for the community scope expand fields.
 */
class CommunityScopeTest extends AbstractAPIv2Test {

    use CommunityApiTestTrait;

    protected static $addons = ['vanilla', 'groups'];


    /**
     * Test scope for a category created with no special permissions.
     */
    public function testCategoryScopeDefault() {
        $this->createCategory();
        $this->createDiscussion();
        $this->createComment();

        // By default categories should be public on a new site.
        $this->assertCategoryScope(CrawlableRecordSchema::SCOPE_PUBLIC);

        // Modify the guest role with particular permissions for this category.
        $this->api()->patch("/roles/".\RoleModel::GUEST_ID, [
            'permissions' => [[
                "id" => $this->lastInsertedCategoryID,
                'type' => "category",
                "permissions" =>  [
                    "discussions.view" => true
                ],
            ]],
        ]);
        $this->assertCategoryScope(CrawlableRecordSchema::SCOPE_PUBLIC);

        // Adjusting some other role should have no effect.
        $this->api()->patch("/roles/".\RoleModel::MEMBER_ID, [
            'permissions' => [[
                "id" => $this->lastInsertedCategoryID,
                'type' => "category",
                "permissions" =>  [
                    "discussions.view" => false
                ],
            ]],
        ]);
        $this->assertCategoryScope(CrawlableRecordSchema::SCOPE_PUBLIC);

        // Make the cateogry and it's contents hidden to guests.
        $this->api()->patch("/roles/".\RoleModel::GUEST_ID, [
            'permissions' => [[
                "id" => $this->lastInsertedCategoryID,
                'type' => "category",
                "permissions" =>  [
                    "discussions.view" => false
                ],
            ]],
        ]);
        $this->assertCategoryScope(CrawlableRecordSchema::SCOPE_RESTRICTED);
    }

    /**
     * Assert that the recent category, discussion, and comment all have the expected scope applied.
     *
     * @param string $expectedScope
     */
    public function assertCategoryScope(string $expectedScope) {
        $fetchedCategory = $this->api()->get(
            "/categories/{$this->lastInsertedCategoryID}",
            ['expand' => ModelUtils::EXPAND_CRAWL]
        )->getBody();

        $this->assertEquals($expectedScope, $fetchedCategory['scope']);

        $fetchedDiscussion = $this->api()->get(
            "/discussions/{$this->lastInsertedDiscussionID}",
            ['expand' => ModelUtils::EXPAND_CRAWL]
        )->getBody();

        $this->assertEquals($expectedScope, $fetchedDiscussion['scope']);

        $fetchedComment = $this->api()->get(
            "/comments/{$this->lastInsertCommentID}",
            ['expand' => ModelUtils::EXPAND_CRAWL]
        )->getBody();

        $this->assertEquals($expectedScope, $fetchedComment['scope']);
    }
}
