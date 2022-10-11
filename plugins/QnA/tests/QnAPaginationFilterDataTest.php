<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use CommentModel;
use Vanilla\QnA\Models\AnswerModel;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test QnA page structured data.
 */
class QnAPaginationFilterDataTest extends SiteTestCase
{
    use QnaApiTestTrait;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array
    {
        return ["vanilla", "qna"];
    }

    /**
     * Tests we have [Next Page] link with Query String param in the response.
     */
    public function testQnAPagination()
    {
        $this->runWithConfig(
            [
                "Vanilla.Discussions.PerPage" => 1,
            ],
            function () {
                // Create questions.
                $this->createCategory();
                $q1 = $this->createQuestion();
                $q2 = $this->createQuestion();

                $deliveryOptions = ["deliveryType" => DELIVERY_TYPE_ALL];

                // Test the /discussions/unanswered pagination codepath.
                $expectedLink = 'href="/qnapaginationfilterdatatest/discussions/unanswered/p2"';
                $view = $this->bessy()->getHtml("/discussions/unanswered", [], $deliveryOptions);
                $view->assertContainsString($expectedLink);
                $view->assertCssSelectorTextContains(".Next", "Next");

                $expectedLink = 'href="/qnapaginationfilterdatatest/discussions/unanswered"';
                $view = $this->bessy()->getHtml("/discussions/unanswered/p2", [], $deliveryOptions);
                $view->assertContainsString($expectedLink);
                $view->assertCssSelectorExists(".Previous", "Previous");

                // Test the query param filtering codepath.
                $a1 = $this->createAnswer(["discussionID" => $q1["discussionID"]]);
                $a2 = $this->createAnswer(["discussionID" => $q2["discussionID"]]);
                $expectedLink = 'href="/qnapaginationfilterdatatest/discussions/p2?qna=answered"';
                $view = $this->bessy()->getHtml("/discussions", ["qna" => "answered"], $deliveryOptions);
                $view->assertContainsString($expectedLink);
                $view->assertCssSelectorTextContains(".Next", "Next");

                $expectedLink = 'href="/qnapaginationfilterdatatest/discussions/?qna=answered"';
                $view = $this->bessy()->getHtml("/discussions/p2", ["qna" => "answered"], $deliveryOptions);
                $view->assertContainsString($expectedLink);
                $view->assertCssSelectorExists(".Previous", "Previous");
            }
        );
    }
}
