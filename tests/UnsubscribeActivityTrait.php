<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests;

use ActivityModel;
use Exception;
use PHPUnit\Framework\Assert;
use UserModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Activity\Activity;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Http\InternalClient;

/**
 * @method InternalClient api()
 */
trait UnsubscribeActivityTrait
{
    /** @var ActivityModel */
    protected $activityModel;

    /** @var UserModel */
    protected $userModel;

    /** @var UserNotificationPreferencesModel */
    protected $userNotificationPreferencesModel;

    /**
     * Initialize the required classes for the UnsubscribeActivityTrait.
     *
     * @return void
     */
    public function setUpUnsubscribeActivityTrait(): void
    {
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->activityService = $this->container()->get(ActivityService::class);
        $this->userNotificationPreferencesModel = $this->container()->get(UserNotificationPreferencesModel::class);
        $this->config = $this->container()->get(ConfigurationInterface::class);
    }

    /**
     *  Test that emails coming from the activities can be unsubscribed from using the `/api/v2/unsubscribe` endpoint.
     *
     * @param $activityName
     * @dataProvider provideActivities
     * @return void
     */
    public function testUnsubscribeActivity($activityName): void
    {
        $this->setUpUnsubscribeActivityTrait();
        $this->unsubscribeFromActivityTest($activityName);
    }

    /**
     * Test that an activity can be properly unsubscribe from.
     *
     * @param $activityName
     * @param $activityUserID
     * @param $notifyUserID
     * @return void
     * @throws Exception
     */
    public function unsubscribeActivityTest($activityName, $activityUserID, $notifyUserID): void
    {
        /** @var Activity $activity */
        $activity = new $activityName();
        $activityRecord = $this->activityModel->save([
            "ActivityType" => $activity::getActivityTypeID(),
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => $activity::getActivityTypeID()],
        ]);

        $this->userModel->savePreference($notifyUserID, [
            "Email." . $activity::getPreference() => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink(
            $activityRecord["ActivityID"],
            $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY),
            "text"
        );

        $this->assertStringContainsString("https", $unsubscribeLink);

        // Call the API endpoint.
        preg_match("~/unsubscribe/(.*)~", $unsubscribeLink, $token);
        $response = $this->api()->post("/unsubscribe/$token[1]}");

        Assert::assertEquals(
            201,
            $response->getStatusCode(),
            "Failed to unsubscribe from " . $activity::getActivityTypeID()
        );

        $preferences = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        Assert::assertEquals(
            0,
            $preferences["Email." . $activity::getPreference()] ?? null,
            "Failed to update the database when unsubscribing from " . $activity::getActivityTypeID()
        );
    }

    /**
     * Test that the specified activity class can be unsubscribed from.
     *
     * @param string $activityName
     * @return void
     */
    public function unsubscribeFromActivityTest(string $activityName): void
    {
        $activityUser = $this->createUser();
        $notifyUser = $this->createUser();
        $this->unsubscribeActivityTest($activityName, $activityUser["userID"], $notifyUser["userID"]);
    }
}
