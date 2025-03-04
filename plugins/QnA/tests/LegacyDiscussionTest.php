<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use Vanilla\FeatureFlagHelper;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

class LegacyDiscussionTest extends SiteTestCase
{
    use QnaApiTestTrait;

    public static $addons = ["QnA"];

    public function testLegacyDiscussionsQnaFilter()
    {
        $this->useLegacyLayouts();
        $this->createCategory();
        $accepted = $this->createQuestion(["name" => "answered question"]);
        $answer = $this->createAnswer();
        $this->acceptAnswer($accepted, $answer);
        $unanswered = $this->createQuestion(["name" => "unanswered"]);

        $result = $this->bessy()
            ->getJsonData("/discussions/unanswered")
            ->getData();

        $this->assertCount(1, $result["Discussions"]);
        $this->assertEquals($unanswered["discussionID"], $result["Discussions"][0]["DiscussionID"]);

        $result = $this->bessy()
            ->getJsonData("/discussions?qna=Accepted")
            ->getData();

        $this->assertCount(1, $result["Discussions"]);
        $this->assertEquals($accepted["discussionID"], $result["Discussions"][0]["DiscussionID"]);

        $test = true;
    }

    /**
     * Test that the QnA tag only show for "question" type posts and not "discussion"
     *
     * @return void
     */
    public function testMetaTagFilteredOutForDiscussion()
    {
        $this->runWithConfig(
            [
                FeatureFlagHelper::featureConfigKey("customLayout.discussionThread") => false,
                FeatureFlagHelper::featureConfigKey("DiscussionQnATag") => true,
            ],
            function () {
                $discussion = $this->createDiscussion();
                $this->bessy()
                    ->getHtml($discussion["url"], [], ["deliveryType" => DELIVERY_TYPE_ALL])
                    ->assertCssSelectorNotExists(".Tag.QnA-Tag-Question");

                $question = $this->createQuestion();
                $this->bessy()
                    ->getHtml($question["url"], [], ["deliveryType" => DELIVERY_TYPE_ALL])
                    ->assertCssSelectorExists(".Tag.QnA-Tag-Question");
            }
        );
    }
}
