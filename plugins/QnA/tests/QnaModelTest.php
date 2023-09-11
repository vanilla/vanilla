<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use DiscussionModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use QnaModel;
use QnAPlugin;
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

    public static $addons = ["QnA"];

    /** @var QnaQuickLinksProvider */
    private $linksProvider;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var QnaModel */
    private $qnAModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->qnAModel = self::container()->get(QnaModel::class);
        $this->discussionModel = self::container()->get(DiscussionModel::class);
    }

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
     * Test QnA status recalculations.
     */
    public function testQnARecounts()
    {
        // Create a few questions with an answer for each.
        $acceptedQuestion = $this->createQuestion();
        $acceptedAnswer = $this->createAnswer();
        $rejectedQuestion = $this->createQuestion();
        $rejectedAnswer = $this->createAnswer();

        $this->setAnswerStatus($acceptedQuestion, $acceptedAnswer, QnaModel::ACCEPTED);
        $this->setAnswerStatus($rejectedQuestion, $rejectedAnswer, QnaModel::REJECTED);

        // Force `statusID` to `0` so we can trigger a recount afterwards.
        $this->discussionModel->setField($acceptedQuestion["discussionID"], "statusID", 0);
        $this->discussionModel->setField($rejectedQuestion["discussionID"], "statusID", 0);

        // Verify that the QnA discussions statuses are improperly set to `0`.
        $blankedAcceptedDiscussion = $this->discussionModel->getID(
            $acceptedQuestion["discussionID"],
            DATASET_TYPE_ARRAY
        );
        $blankedRejectedDiscussion = $this->discussionModel->getID(
            $rejectedQuestion["discussionID"],
            DATASET_TYPE_ARRAY
        );
        $this->assertEquals(0, $blankedAcceptedDiscussion["statusID"]);
        $this->assertEquals(0, $blankedRejectedDiscussion["statusID"]);

        // Trigger recounts
        $this->qnAModel->counts("statusID");

        // Verify that the QnA discussions statuses are properly set again.
        $recountedAcceptedDiscussion = $this->discussionModel->getID(
            $acceptedQuestion["discussionID"],
            DATASET_TYPE_ARRAY
        );
        $recountedRejectedDiscussion = $this->discussionModel->getID(
            $rejectedQuestion["discussionID"],
            DATASET_TYPE_ARRAY
        );
        $this->assertEquals(QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $recountedAcceptedDiscussion["statusID"]);
        $this->assertEquals(QnAPlugin::DISCUSSION_STATUS_REJECTED, $recountedRejectedDiscussion["statusID"]);
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
