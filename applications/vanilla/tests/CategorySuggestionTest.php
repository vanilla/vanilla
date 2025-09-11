<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Models\CategorySuggestionModel;
use Vanilla\OpenAI\OpenAIClient;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the CategorySuggestionModel for suggesting categories.
 */
class CategorySuggestionTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    private ConfigurationInterface $configuration;
    private CategorySuggestionModel $recommendationModel;
    private bool $mockRun = false;

    protected static $addons = ["QnA", "ideation"];

    /**
     * @inheridoc
     */
    public function setUp(): void
    {
        $this->resetTable("Category");

        parent::setUp();
        $this->generateStartingCategories();
        $this->config = $this->container()->get(ConfigurationInterface::class);
        $url = getenv("OPEN_AI_DEPLOYMENT_URL");
        $secret = getenv("OPEN_AI_SECRET");

        if ($url && $secret) {
            $this->config->saveToConfig([
                OpenAIClient::CONF_GPT4_ENDPOINT => $url,
                OpenAIClient::CONF_GPT4_SECRET => $secret,
            ]);
            $openAIClient = new OpenAIClient($this->config);
            $this->container()->setInstance(OpenAIClient::class, $openAIClient);
        } else {
            // OpenAI is not configured, use mock client
            $this->mockRun = true;
            $mockOpenAIClient = $this->container()->get(MockOpenAIClient::class);
            $this->setMockResponse($mockOpenAIClient);
            $this->container()->setInstance(OpenAIClient::class, $mockOpenAIClient);
        }

        $this->recommendationModel = $this->container()->get(CategorySuggestionModel::class);
    }

    /**
     * Test suggesting categories for a discussion.
     *
     * @return void
     */
    public function testSuggestingCategory(): void
    {
        $results = $this->recommendationModel->suggestCategory("Hi! I need help.");
        $this->assertSuggestionFound($results, $this->support["categoryID"], "question");

        // 1 is the General category created by default
        $this->assertSuggestionFound($results, $this->general["categoryID"], "discussion");
    }

    /**
     * Test that a single site configuration for categories works correctly.
     *
     * @return void
     */
    public function testSingleSiteConfigCategory(): void
    {
        $this->runWithConfig(
            [CategorySuggestionModel::VALID_IDS_CONFIG_KEY => [$this->unrelated["categoryID"]]],
            function () {
                $results = $this->recommendationModel->suggestCategory("What is the meaning of life?");
                $this->assertSuggestionFound($results, $this->unrelated["categoryID"], "question");
            }
        );
    }

    /**
     * Test that no category suggestions are made when the site configuration does not allow it.
     *
     * @return void
     */
    public function testNoSiteConfigCategory(): void
    {
        $this->runWithConfig([CategorySuggestionModel::VALID_IDS_CONFIG_KEY => []], function () {
            $results = $this->recommendationModel->suggestCategory("Hi! I need help.");
            $this->assertEmpty($results, "Expected no category suggestions when no valid categories are configured.");
        });
    }

    /**
     * Test that no post type suggestions are made when the site configuration does not allow it.
     *
     * @return void
     */
    public function testNoSiteConfigPostType(): void
    {
        $this->runWithConfig([CategorySuggestionModel::VALID_POST_TYPES_CONFIG_KEY => []], function () {
            $results = $this->recommendationModel->suggestCategory("Hi! I need help.");
            $this->assertEmpty($results, "Expected no category suggestions when no valid post types are configured.");
        });
    }

    /**
     * Test that guest are not allowed to get category suggestions.
     *
     * @return void
     */
    public function testAsGuest(): void
    {
        $this->runWithUser(function () {
            $results = $this->recommendationModel->suggestCategory("Hi! I need help.");
            $this->assertEmpty($results);
        }, 0);
    }

    /**
     * Test when a user has no access to the category.
     *
     * @return void
     */
    public function testNoAccessToCategory(): void
    {
        $user = $this->createUserWithCategoryPermissions($this->techSupport, [
            "discussions.view" => true,
            "discussions.add" => false,
        ]);
        $this->runWithUser(function () {
            $results = $this->recommendationModel->suggestCategory("My device is broken. Help me!");
            $this->assertSuggestionNotFound($results, $this->techSupport["categoryID"], "discussion");
            $this->assertSuggestionNotFound($results, $this->techSupport["categoryID"], "question");
        }, $user);
    }

    /**
     * Test that the confidence score is respected when suggesting categories.
     *
     * @return void
     */
    public function testConfidenceScore(): void
    {
        $this->runWithConfig([CategorySuggestionModel::MIN_CONFIDENCE_CONFIG_KEY => 0.85], function () {
            $results = $this->recommendationModel->suggestCategory("Hi! I need help.");
            $this->assertSuggestionFound($results, $this->support["categoryID"], "question");
            $this->assertSuggestionNotFound($results, $this->general["categoryID"], "discussion");
        });
    }

    /**
     * Test that we will not return invalid categories suggested by the LLM.
     *
     * @return void
     */
    public function testLlmRecommendInvalidCategory(): void
    {
        if (!$this->mockRun) {
            $this->markTestSkipped("This test is not applicable when using the real OpenAI client.");
        }

        $results = $this->recommendationModel->suggestCategory("This will return an invalid category.");
        $this->assertEmpty(
            $results["category"],
            "Expected no valid categories to be returned for an invalid category suggestion."
        );
    }

    /**
     * Test that we will not return invalid post types suggested by the LLM.
     *
     * @return void
     */
    public function testLlmRecommendInvalidPostType(): void
    {
        if (!$this->mockRun) {
            $this->markTestSkipped("This test is not applicable when using the real OpenAI client.");
        }

        $results = $this->recommendationModel->suggestCategory("This will return an invalid post type.");
        $this->assertEmpty(
            $results["category"],
            "Expected no valid categories to be returned for an invalid post type suggestion."
        );
    }

    // UTILS

    /**
     * Generate a bunch of starting categories for testing.
     *
     * @return void
     */
    public function generateStartingCategories(): void
    {
        $this->general = $this->createCategory([
            "name" => "General",
            "Description" => "General discussions about the community.",
            "AllowedDiscussionTypes" => ["Discussion"],
        ]);
        $this->announcement = $this->createCategory([
            "name" => "Announcements",
            "Description" => "Official announcements from the team.",
        ]);
        $this->feedback = $this->createCategory([
            "name" => "Feedback",
            "Description" => "Your feedback is important to us.",
            "AllowedDiscussionTypes" => ["Idea"],
        ]);
        $this->offTopic = $this->createCategory([
            "name" => "Off-topic",
            "Description" => "Discussions that don't fit anywhere else.",
        ]);
        $this->billing = $this->createCategory([
            "name" => "Billing",
            "Description" => "Billing and subscription related discussions.",
        ]);
        $this->support = $this->createCategory([
            "name" => "Support",
            "Description" => "Get help with your issues.",
            "AllowedDiscussionTypes" => ["Question", "Discussion"],
        ]);
        $this->techSupport = $this->createCategory([
            "name" => "Tech Support",
            "Description" => "Technical support for our products.",
            "AllowedDiscussionTypes" => ["Question", "Discussion"],
        ]);

        $this->unrelated = $this->createCategory([
            "name" => "The name of this category doesn't matter",
            "Description" => "And so does the description.",
            "AllowedDiscussionTypes" => ["Question", "Discussion"],
        ]);
    }

    /**
     * Register mock responses for the OpenAI Mock client.
     *
     * @param MockOpenAIClient $mockOpenAIClient
     * @return void
     */
    public function setMockResponse(MockOpenAIClient &$mockOpenAIClient): void
    {
        $mockOpenAIClient->addMockResponse(json_encode("Hi! I need help."), [
            "category" => [
                [
                    "id" => $this->support["categoryID"],
                    "confidence" => 0.9,
                    "type" => "question",
                ],
                [
                    "id" => $this->general["categoryID"],
                    "confidence" => 0.7,
                    "type" => "discussion",
                ],
                [
                    "id" => $this->announcement["categoryID"],
                    "confidence" => 0.5,
                    "type" => "question",
                ],
            ],
        ]);

        $mockOpenAIClient->addMockResponse(json_encode("What is the meaning of life?"), [
            "category" => [
                [
                    "id" => $this->unrelated["categoryID"],
                    "confidence" => 0.9,
                    "type" => "question",
                ],
            ],
        ]);

        $mockOpenAIClient->addMockResponse(json_encode("My device is broken. Help me!"), [
            "category" => [
                [
                    "id" => $this->general["categoryID"],
                    "confidence" => 0.7,
                    "type" => "discussion",
                ],
                [
                    "id" => $this->announcement["categoryID"],
                    "confidence" => 0.6,
                    "type" => "question",
                ],
            ],
        ]);

        $mockOpenAIClient->addMockResponse(json_encode("This will return an invalid category."), [
            "category" => [
                [
                    "id" => -1000,
                    "confidence" => 1,
                    "type" => "discussion",
                ],
            ],
        ]);

        $mockOpenAIClient->addMockResponse(json_encode("This will return an invalid post type."), [
            "category" => [
                [
                    "id" => $this->general["categoryID"],
                    "confidence" => 1,
                    "type" => "invalid",
                ],
            ],
        ]);
    }

    /**
     * Asserts that a specific suggestion was found in the results.
     *
     * @param array $results
     * @param string $type
     * @param int $id
     * @return void
     */
    public function assertSuggestionFound(array $results, int $id, string $type): void
    {
        $found = false;
        foreach ($results["category"] as $item) {
            if ($item["id"] === $id && $item["type"] === $type) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected to find {$type} with ID {$id} but it was not found in the result.");
    }

    /**
     * Asserts that a specific suggestion was found in the results.
     *
     * @param array $results
     * @param string $type
     * @param int $id
     * @return void
     */
    public function assertSuggestionNotFound(array $results, int $id, string $type): void
    {
        foreach ($results["category"] ?? [] as $item) {
            if ($item["id"] === $id && $item["type"] === $type) {
                $this->assertFalse(
                    false,
                    "Expected not to find {$type} with ID {$id} but it was not found in the result."
                );
            }
        }
        $this->assertTrue(true);
    }
}
