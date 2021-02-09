<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

use Vanilla\QnA\Models\AnswerModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Trait QnaTestHelperTrait
 */
trait QnaApiTestTrait {

    use CommunityApiTestTrait;

    /** @var int|null */
    private $lastInsertedQuestionID = null;

    /** @var int|null */
    private $lastInsertedAnswerID = null;

    /**
     * Setup.
     */
    public function setUpQnaApiTestTrait() {
        $this->lastInsertedQuestionID = null;
        $this->lastInsertedAnswerID = null;
    }

    /**
     * Create a Question.
     *
     * @param array $overrides
     * @return array
     */
    public function createQuestion(array $overrides = []): array {
        $categoryID = $overrides['categoryID'] ?? $this->lastInsertedCategoryID;
        if ($categoryID === null) {
            throw new \Exception('Could not insert a test discussion because no category was specified.');
        }
        $question = $this->api()->post('discussions/question', $overrides + [
            'categoryID' => $categoryID,
            'name' => 'Question',
            'body' => 'Question being asked!',
            'format' => 'markdown',
        ])->getBody();
        $this->lastInsertedQuestionID = $this->lastInsertedDiscussionID = $question['discussionID'];
        return $question;
    }

    /**
     * Create a Question.
     *
     * @param array $overrides
     * @return array
     */
    public function createAnswer(array $overrides = []): array {
        $questionID = $overrides['discussionID'] ?? $this->lastInsertedQuestionID;
        if ($questionID === null) {
            throw new \Exception('Could not insert a test answer because no question was specified.');
        }
        $record = $overrides + [
            'discussionID' => $questionID,
            'body' => 'Answering',
            'format' => 'markdown',
        ];
        $answer = $this->api()->post('comments', $record)->getBody();
        $this->lastInsertedAnswerID = $this->lastInsertCommentID = $answer['commentID'];
        return $answer;
    }

    /**
     * Assert an array has all necessary question fields.
     *
     * @param array $discussion
     * @param array $expectedAttributes
     */
    protected function assertIsQuestion($discussion, $expectedAttributes = []) {
        $this->assertIsArray($discussion);

        $this->assertArrayHasKey('type', $discussion);
        $this->assertEquals('question', $discussion['type']);

        $this->assertArrayHasKey('attributes', $discussion);
        $this->assertArrayHasKey('question', $discussion['attributes']);

        $this->assertArrayHasKey('status', $discussion['attributes']['question']);
        $this->assertArrayHasKey('dateAccepted', $discussion['attributes']['question']);
        $this->assertArrayHasKey('dateAnswered', $discussion['attributes']['question']);

        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals($value, $discussion['attributes']['question'][$attribute]);
        }
    }

    /**
     * Assert an array has all necessary answer fields.
     *
     * @param array $comment
     * @param array $expectedAttributes
     */
    protected function assertIsAnswer($comment, $expectedAttributes = []) {
        $this->assertIsArray($comment);

        $this->assertArrayHasKey('attributes', $comment);
        $this->assertArrayHasKey('answer', $comment['attributes']);

        $this->assertArrayHasKey('status', $comment['attributes']['answer']);
        $this->assertArrayHasKey('dateAccepted', $comment['attributes']['answer']);
        $this->assertArrayHasKey('acceptUserID', $comment['attributes']['answer']);

        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals($value, $comment['attributes']['answer'][$attribute]);
        }
    }

    /**
     * Accept an answer.
     *
     * @param array $question
     * @param array $answer
     */
    public function acceptAnswer(array $question, array $answer) {
        /** @var AnswerModel $model */
        $model = \Gdn::getContainer()->get(AnswerModel::class);
        $model->updateCommentQnA($question, $answer, 'Accepted');
        $this->recalculateDiscussionQnA($question);
    }

    /**
     * Recalculate the QnA status of a discussion.
     *
     * @param array $question
     */
    public function recalculateDiscussionQnA(array $question) {
        /** @var \QnAPlugin $plugin */
        $plugin = \Gdn::getContainer()->get(\QnAPlugin::class);
        $plugin->recalculateDiscussionQnA($question);
    }
}
