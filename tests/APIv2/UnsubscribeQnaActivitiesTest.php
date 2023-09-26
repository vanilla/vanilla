<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\QnA\Activity\AnswerAcceptedActivity;
use Vanilla\QnA\Activity\QuestionAnswerActivity;
use Vanilla\QnA\Activity\QuestionFollowUpActivity;
use VanillaTests\SiteTestCase;
use VanillaTests\UnsubscribeActivityTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test that QnA activities can be unsubscribed from.
 */
class UnsubscribeQnaActivitiesTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use UnsubscribeActivityTrait;

    /** @var string[] */
    public static $addons = ["qna"];

    /**
     * Provide the QnA activities.
     *
     * @return array[]
     */
    public static function provideActivities(): array
    {
        $r = [
            "AnswerAcceptedActivity" => [AnswerAcceptedActivity::class],
            "QuestionAnswerActivity" => [QuestionAnswerActivity::class],
            "QuestionFollowUpActivity" => [QuestionFollowUpActivity::class],
        ];
        return $r;
    }
}
