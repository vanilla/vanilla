<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license MIT
 */

namespace Jobs;

use Vanilla\Forum\Jobs\EmailDigestContentJob;
use Vanilla\Models\UserDigestModel;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\EmailDigestGeneratorTest;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for the email digest content generation cron.
 */
class TestEmailDigestContentJob extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    private UserDigestModel $userDigestModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userDigestModel = $this->container()->get(UserDigestModel::class);
        $config = [
            "Garden.Email.Disabled" => false,
            "Feature.Digest.Enabled" => true,
            "Garden.Digest.Enabled" => true,
        ];
        \Gdn::config()->saveToConfig($config);
    }

    /**
     * Test that the email digest content content job generates digest content for site users
     */
    public function testCronjobs()
    {
        $data = $this->generateTestData();
        $job = new EmailDigestContentJob();
        $result = $job->run();
        $this->assertLog([
            "level" => "info",
            "channel" => "system",
            "tags" => ["digest-content-generator"],
            "message" => "Generating digest content.",
        ]);
        $this->assertEquals(JobExecutionStatus::complete(), $result);
        $this->assertLogMessage("Digest content successfully generated.");
        $records = $this->userDigestModel->select();
        $this->assertCount(3, $records);

        foreach ($records as $record) {
            $digestCategories = $record["attributes"]["digestCategories"];
            if (count($digestCategories) == 1) {
                $categoryID = $digestCategories[0];
                $digestUser = $record["attributes"]["digestUsers"][0];
                $this->assertTrue($record["attributes"]["digestWithPreference"]);
                $this->assertEquals($data["category"][$categoryID]["followedUsers"], [$digestUser]);
            } else {
                $this->assertFalse($record["attributes"]["digestWithPreference"]);
                $usersWithOutPreference = [$data["UserIDs"][0], $data["UserIDs"][2]];
                $this->assertEquals($usersWithOutPreference, $record["attributes"]["digestUsers"]);
                $this->assertEmpty(array_diff($data["categoryIDs"], $record["attributes"]["digestCategories"]));
            }

            $this->assertNotEmpty($record["digestContent"]);
        }
    }

    /**
     * Generate some test data for validating the test
     *
     * @return array
     */
    public function generateTestData(): array
    {
        $this->resetTable("UserCategory");
        $data = [];
        $category = [];
        for ($i = 0; $i < 4; $i++) {
            $this->createCategory([
                "name" => "Digest Test Category{$i}",
                "parentCategoryID" => -1,
            ]);
            $data["categoryIDs"][] = $this->lastInsertedCategoryID;
            $this->createDiscussion([
                "name" => "Test Digest Discussion {$i}",
                "categoryID" => $this->lastInsertedCategoryID,
                "body" => "Test Digest content for the users",
            ]);
            $category[$this->lastInsertedCategoryID]["discussions"][] = $this->lastInsertedDiscussionID;
            $data["discussionIDs"][] = $this->lastInsertedDiscussionID;

            $this->createUser([
                "name" => "Digest User {$i}",
            ]);

            $data["UserIDs"][] = $this->lastUserID;
        }
        $preferences = EmailDigestGeneratorTest::getPreferences();
        foreach ($data["UserIDs"] as $index => $userID) {
            \Gdn::userMetaModel()->setUserMeta($userID, "Preferences.Email.DigestEnabled", 1);
            if (($index + 1) % 2 == 0) {
                $categoryID = $data["categoryIDs"][$index];
                $this->api()->patch("/categories/{$categoryID}/preferences/{$userID}", [
                    \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                    \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => true,
                ]);
                $category[$categoryID]["followedUsers"][] = $userID;
            }

            $discussionID = $data["discussionIDs"][$index];
            $this->runWithUser(function () use ($discussionID, $userID) {
                $this->createComment([
                    "discussionID" => $discussionID,
                    "body" => "This is a comment done by user {$userID}",
                ]);
            }, $userID);
        }

        $data["category"] = $category;

        return $data;
    }
}
