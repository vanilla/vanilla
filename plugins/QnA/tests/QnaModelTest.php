<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use Vanilla\QnA\Models\QnaQuickLinksProvider;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the QnA model.
 */
class QnaModelTest extends SiteTestCase
{
    use QnaApiTestTrait;
    use EventSpyTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["vanilla", "QnA"];

    /** @var QnaQuickLinksProvider */
    private $linksProvider;

    /**
     * Test unanswered count fetching, caching, and permissions.
     */
    public function testUnansweredCounts()
    {
        // Create an accepted answer question.
        $question = $this->createQuestion();
        $answer = $this->createAnswer();
        $this->acceptAnswer($question, $answer);

        // Create the unanswered records.
        $this->createQuestion();
        $this->createQuestion();
        $permCat = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID]);
        $this->createQuestion();

        // Our default admin user has access to all 3.
        $this->assertCounts(3);
        $this->assertCounts(3, 3);
        // The limit parameter is respected.
        $this->assertCounts(1, 1);

        $this->runWithUser(function () {
            // CategoryModel doesn't recognize the session changing mid-request.
            \CategoryModel::clearCache();

            // Guest user doesn't have access to the 3rd question.
            $this->assertCounts(2);
            $this->assertCounts(2, 3);
            $this->assertCounts(1, 1);
        }, \UserModel::GUEST_USER_ID);

        // Test with alternate visible categories.
        \CategoryModel::clearCache();

        $handler = [
            "getAlternateVisibleCategories",
            function () use ($permCat) {
                return [\CategoryModel::categories($permCat["categoryID"])];
            },
        ];
        $this->getEventManager()->bind(...$handler);
        // Different set of categories again.
        $this->assertCounts(1);
        $this->getEventManager()->unbind(...$handler);

        // Other use with same category access as guest will get the same cache.
        $memberUser = $this->createUser();
        $this->resetTable("Discussion", false);
        $this->runWithUser(function () {
            // CategoryModel doesn't recognize the session changing mid-request.
            \CategoryModel::clearCache();

            // Member user gets the "stale" cache values because they are the same permission set as the guest.
            $this->assertCounts(2);
            $this->assertCounts(2, 3);
            $this->assertCounts(1, 1);
        }, $memberUser);
    }

    /**
     * Test that questions are added to allowed discussion types.
     */
    public function testCategoryModelAllowedDiscussionTypes(): void
    {
        $category = $this->createCategory();
        $allowedTypes = \CategoryModel::getAllowedDiscussionTypes($category);
        $this->assertEqualsCanonicalizing($allowedTypes, ["discussion", "question"]);
    }

    /**
     * Assert that there is a certain unsanswered count.
     *
     * @param int $expected
     * @param int|null $limit
     * @param string $message
     */
    private function assertCounts(int $expected, int $limit = null, string $message = "")
    {
        $actual = $this->getQnaModel()->getUnansweredCount($limit);
        $this->assertEquals($expected, $actual, $message);
    }
}
