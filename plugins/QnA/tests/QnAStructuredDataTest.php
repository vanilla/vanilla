<?php
/**
 * @author Raphaël Bergina <rbergina@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace QnA\Tests;

use Vanilla\QnA\Models\AnswerModel;
use Vanilla\Web\AbstractJsonLDItem;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\EventSpyTestTrait;


use VanillaTests\VanillaTestCase;

/**
 * Test QnA page structured data.
 */
class QnAStructuredDataTest extends VanillaTestCase {
    use SiteTestTrait, SetupTraitsTrait, EventSpyTestTrait, QnaApiTestTrait;

    /** @var AnswerModel */
    private $answerModel;

    /** @var int */
    private $categoryID;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setUpTestTraits();

        $this->categoryID = $this->createCategory()["categoryID"];

        $this->container()->call(function (AnswerModel $answerModel) {
            $this->answerModel = $answerModel;
        });
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void {
        // It seems like class exist even if module is not enabled.
        // We don't really need 'replies'.
        self::$addons = ['vanilla', 'qna', 'replies'];
        parent::setUpBeforeClass();
        self::setUpBeforeClassTestTraits();
    }

    /**
     * Tests we have QAPage structured data in the response.
     */
    public function testQnaPageStructuredData() {
        $question = $this->createQuestion([
            'categoryID' => $this->categoryID,
            'name' => 'Question 1',
            'body' => 'Question 1',
        ]);

        $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 1',
            'body' => 'Answer 1',
        ]);

        $answer2 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 2',
            'body' => 'Answer 2',
        ]);
        $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 3',
            'body' => 'Answer 3',
        ]);
        $answer4 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 4',
            'body' => 'Answer 4',
        ]);

        $this->answerModel->updateCommentQnA($question, $answer2, 'Accepted');
        $this->answerModel->updateCommentQnA($question, $answer4, 'Accepted');
        $this->recalculateDiscussionQnA($question);
        $response = $this->bessy()->get('/discussion/'.$question['discussionID']);
        $jsonLDItems = array_map(function (AbstractJsonLDItem $item) {
            return $item->calculateValue()->getData();
        }, $response->Head->getJsonLDItems());
        $schemaTypes = array_column($jsonLDItems, '@type');
        $index = array_keys($schemaTypes, "QAPage");
        $this->assertContains("QAPage", $schemaTypes);
        $this->assertCount(1, $index);
        $this->assertCount(2, $jsonLDItems[$index[0]]['mainEntity']['acceptedAnswer']);
    }
}
