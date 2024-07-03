<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use DiscussionModel;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Automated tests for AiSuggestionSourceService
 */
class AISuggestionModelTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["qna"];

    private DiscussionModel $discussionModel;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        \Gdn::config()->saveToConfig([
            "Feature.AISuggestions.Enabled" => true,
            "aiSuggestions" => [
                "enabled" => true,
                "userID" => $this->lastUserID,
                "sources" => ["mockSuggestion" => ["enabled" => true]],
            ],
        ]);
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
    }

    /**
     * Test generation of suggestions
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     */
    public function testGenerationofSuggestion()
    {
        $discussion = $this->createDiscussion(["type" => "question"]);

        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];
        $this->assertCount(3, $suggestions);
        $this->assertSame($suggestions[0], [
            "format" => "Vanilla",
            "type" => "mockSuggestion",
            "id" => 0,
            "url" => "someplace.com/here",
            "title" => "answer 1",
            "summary" => "This is how you do this.",
            "hidden" => false,
        ]);

        $createdComments = $this->runWithUser(function () use ($discussion) {
            $suggestion = $this->container()->get(AiSuggestionSourceService::class);
            return $suggestion->createComments($discussion["discussionID"], [0, 2]);
        }, $discussion["insertUserID"]);
        $this->assertCount(2, $createdComments);
        $this->assertSame(\QnaModel::ACCEPTED, $createdComments[0]["qnA"]);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $updatedDiscussion["statusID"]);
    }

    /**
     * Test generation of suggestions
     *
     * @dataProvider ConfigDataProvider
     */
    public function testGenerationOfSuggestionTurnedOffConfig(array $config, $exception = "")
    {
        if ($exception != "") {
            $this->expectExceptionMessage($exception);
        }
        $this->runWithConfig($config, function () {
            $discussion = $this->createDiscussion(["type" => "question"]);

            $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
            $suggestions = $newDiscussion["Attributes"]["suggestions"] ?? [];
            $this->assertCount(0, $suggestions);
        });
    }

    /**
     * Provide data for config cversions
     *
     * @return array
     */
    public function ConfigDataProvider(): array
    {
        $result = [
            "Feature Flag Off" => [["Feature.AISuggestions.Enabled" => false]],
            "Feature turned off" => [["aiSuggestions" => ["enabled" => false]]],
            "Provider turned off" => [
                [
                    "aiSuggestions" => [
                        "enabled" => true,
                        "sources" => ["mockSuggestion" => ["enabled" => false]],
                    ],
                ],
            ],
        ];
        return $result;
    }
}
