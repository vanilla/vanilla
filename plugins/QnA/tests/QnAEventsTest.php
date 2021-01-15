<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace QnA\Tests;

use Vanilla\QnA\Models\AnswerModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\EventSpyTestTrait;

use Garden\Events\ResourceEvent;

use VanillaTests\VanillaTestCase;

/**
 * Test QnA events are working.
 */
class QnAEventsTest extends VanillaTestCase {
    use CommunityApiTestTrait, SiteTestTrait, SetupTraitsTrait, EventSpyTestTrait;

    /** @var AnswerModel */
    private $answerModel;

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array {
        return ["vanilla", "qna"];
    }

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setUpTestTraits();

        $this->container()->call(function (AnswerModel $answerModel, \DiscussionModel $discussionModel) {
            $this->answerModel = $answerModel;
            $this->discussionModel = $discussionModel;
        });
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::setUpBeforeClassTestTraits();
    }

    /**
     * Tests event upon an answer to a question is chosen.
     */
    public function testQnaChosenAnswerEvent() {
        $question = $this->createQuestion([
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $answer1 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 1',
            'body' => 'Answer 1'
        ]);

        $answer2 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 2',
            'body' => 'Answer 2'
        ]);

        $answer3 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 3',
            'body' => 'Answer 3'
        ]);

        $this->answerModel->updateCommentQnA($question, $answer2, 'Accepted');

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'answer',
                ResourceEvent::ACTION_UPDATE,
                [
                    'commentID' => $answer2['commentID'],
                    'discussionID' => $question['discussionID'],
                    'qnA' => "Accepted"
                ]
            )
        );
    }

    /**
     * Create a Question.
     *
     * @param array $body
     * @return array
     */
    public function createQuestion(array $body = []): array {
        $categoryID = $body['categoryID'] ?? $this->lastInsertedCategoryID;
        if (!$categoryID) {
            $category = $this->createCategory();
            $categoryID = $category['categoryID'];
        }

        return $this->api()->post('discussions/question', [
            'categoryID' => $categoryID ?? $this->lastInsertedCategoryID,
            'name' => $body['name'] ?? 'Question',
            'body' => $body['body'] ?? 'Question being asked!',
            'format' => 'markdown',
        ])->getBody();
    }

    /**
     * Create a Question.
     *
     * @param array $body
     * @return array
     */
    public function createAnswer(array $body): array {
        if (!$body['discussionID']) {
            $question = $this->createQuestion();
            $body['discussionID'] = $question['discussionID'];
        }

        $record = [
            'discussionID' => $body['discussionID'],
            'body' => 'Answering',
            'format' => 'markdown',
        ];
        return $this->api()->post('comments', $record)->getBody();
    }
}
