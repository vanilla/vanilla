<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Models\TagSuggestionModel;
use Vanilla\OpenAI\OpenAIClient;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the TagSuggestionModel for suggesting tags.
 */
class TagSuggestionTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    private ConfigurationInterface $config;
    private TagSuggestionModel $tagSuggestionModel;
    private bool $mockRun = false;

    // Test tags
    private array $phpTag;
    private array $javascriptTag;
    private array $helpTag;
    private array $bugTag;
    private array $featureTag;
    private array $supportTag;

    protected static $addons = ["vanilla", "ideation"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->resetTable("Tag");
        $this->resetTable("TagDiscussion");

        parent::setUp();
        $this->generateStartingTags();
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

        $this->tagSuggestionModel = $this->container()->get(TagSuggestionModel::class);
    }

    /**
     * Test basic tag suggestion functionality.
     *
     * @return void
     */
    public function testSuggestingTags(): void
    {
        $results = $this->tagSuggestionModel->suggestTags("I'm having trouble with my PHP code. Can someone help?");

        $this->assertTagSuggestionFound($results, $this->phpTag["tagID"]);
        $this->assertTagSuggestionFound($results, $this->helpTag["tagID"]);
        $this->assertTagSuggestionFound($results, $this->supportTag["tagID"]);
    }

    /**
     * Test that configuration for valid tag types works correctly.
     *
     * @return void
     */
    public function testValidTagTypesConfiguration(): void
    {
        $this->runWithConfig([TagSuggestionModel::VALID_TAGS_CONFIG_KEY => [$this->phpTag["tagID"]]], function () {
            $results = $this->tagSuggestionModel->suggestTags("I'm having trouble with my PHP code. Can someone help?");
            $this->assertTagSuggestionFound($results, $this->phpTag["tagID"]);
            $this->assertTagSuggestionNotFound($results, $this->helpTag["tagID"]);
            $this->assertTagSuggestionNotFound($results, $this->supportTag["tagID"]);
        });
    }

    /**
     * Test that empty valid tag types configuration returns no suggestions.
     *
     * @return void
     */
    public function testEmptyValidTagTypesConfiguration(): void
    {
        $this->runWithConfig([TagSuggestionModel::VALID_TAGS_CONFIG_KEY => []], function () {
            $results = $this->tagSuggestionModel->suggestTags("I'm having trouble with my PHP code. Can someone help?");
            $this->assertEmpty(
                $results["tags"] ?? [],
                "Expected no tag suggestions when no valid tag types are configured."
            );
        });
    }

    /**
     * Test tag limit configuration.
     *
     * @return void
     */
    public function testTagLimitConfiguration(): void
    {
        $this->runWithConfig([TagSuggestionModel::LIMIT_CONFIG_KEY => 0], function () {
            $results = $this->tagSuggestionModel->suggestTags("I'm having trouble with my PHP code. Can someone help?");
            $this->assertEmpty($results["tags"] ?? [], "Expected no tag suggestions when tag limit is set to 0.");
        });

        $this->runWithConfig([TagSuggestionModel::LIMIT_CONFIG_KEY => 2], function () {
            $results = $this->tagSuggestionModel->suggestTags("Need help with JavaScript");
            $this->assertNotEmpty($results);
        });
    }

    /**
     * Test that confidence score filtering works correctly.
     *
     * @return void
     */
    public function testConfidenceScoreFiltering(): void
    {
        $this->runWithConfig([TagSuggestionModel::MIN_CONFIDENCE_CONFIG_KEY => 0.85], function () {
            $results = $this->tagSuggestionModel->suggestTags("I need help with PHP programming");

            // Should only find high-confidence suggestions
            $this->assertTagSuggestionFound($results, $this->phpTag["tagID"]);

            // Help tag should be filtered out due to lower confidence (0.8)
            $this->assertTagSuggestionNotFound($results, $this->helpTag["tagID"]);
        });
    }

    /**
     * Test tag suggestion count configuration.
     *
     * @return void
     */
    public function testSuggestionCountConfiguration(): void
    {
        $this->runWithConfig([TagSuggestionModel::SUGGESTION_COUNT => 2], function () {
            $results = $this->tagSuggestionModel->suggestTags("Need help with JavaScript programming");
            $this->assertCount(2, $results["tags"] ?? []);
        });
    }

    /**
     * Test that LLM hallucination (invalid tag IDs) is handled correctly.
     *
     * @return void
     */
    public function testLlmHallucinationHandling(): void
    {
        if (!$this->mockRun) {
            $this->markTestSkipped("This test is not applicable when using the real OpenAI client.");
        }

        $results = $this->tagSuggestionModel->suggestTags("This will return invalid tag suggestions");

        $this->assertEmpty(
            $results["tags"] ?? [],
            "Expected no valid tags to be returned for invalid tag suggestions from LLM."
        );
    }

    /**
     * Test handling of empty tag response from LLM.
     *
     * @return void
     */
    public function testEmptyTagResponse(): void
    {
        if (!$this->mockRun) {
            $this->markTestSkipped("This test is not applicable when using the real OpenAI client.");
        }

        $results = $this->tagSuggestionModel->suggestTags("This will return no tags");

        $this->assertEmpty($results["tags"] ?? []);
    }

    // UTILS

    /**
     * Generate starting tags for testing.
     *
     * @return void
     */
    public function generateStartingTags(): void
    {
        $this->phpTag = $this->createTag([
            "name" => "php",
            "fullName" => "PHP",
        ]);

        $this->javascriptTag = $this->createTag([
            "name" => "javascript",
            "fullName" => "JavaScript",
        ]);

        $this->helpTag = $this->createTag([
            "name" => "help",
            "fullName" => "Help",
        ]);

        $this->bugTag = $this->createTag([
            "name" => "bug",
            "fullName" => "Bug",
        ]);

        $this->featureTag = $this->createTag([
            "name" => "feature",
            "fullName" => "Feature Request",
        ]);

        $this->supportTag = $this->createTag([
            "name" => "support",
            "fullName" => "Support",
        ]);
    }

    /**
     * Set up mock responses for the OpenAI client.
     *
     * @param MockOpenAIClient $mockOpenAIClient
     * @return void
     */
    public function setMockResponse(MockOpenAIClient &$mockOpenAIClient): void
    {
        // Mock response for PHP help request
        $mockOpenAIClient->addMockResponse(json_encode("I'm having trouble with my PHP code. Can someone help?"), [
            "labels" => [
                [
                    "id" => $this->phpTag["tagID"],
                    "label" => "PHP",
                    "confidence" => 0.95,
                ],
                [
                    "id" => $this->helpTag["tagID"],
                    "label" => "Help",
                    "confidence" => 0.8,
                ],
                [
                    "id" => $this->supportTag["tagID"],
                    "label" => "Support",
                    "confidence" => 0.75,
                ],
            ],
        ]);

        // Mock response for JavaScript issues
        $mockOpenAIClient->addMockResponse(json_encode("JavaScript async/await issues"), [
            "labels" => [
                [
                    "id" => $this->javascriptTag["tagID"],
                    "label" => "JavaScript",
                    "confidence" => 0.9,
                ],
                [
                    "id" => $this->bugTag["tagID"],
                    "label" => "Bug",
                    "confidence" => 0.7,
                ],
            ],
        ]);

        // Mock response for JavaScript help (with valid tag types)
        $mockOpenAIClient->addMockResponse(json_encode("Need help with JavaScript"), [
            "labels" => [
                [
                    "id" => $this->javascriptTag["tagID"],
                    "label" => "JavaScript",
                    "confidence" => 0.9,
                ],
                [
                    "id" => $this->helpTag["tagID"],
                    "label" => "Help",
                    "confidence" => 0.8,
                ],
            ],
        ]);

        // Mock response for PHP programming with high confidence threshold
        $mockOpenAIClient->addMockResponse(json_encode("I need help with PHP programming"), [
            "labels" => [
                [
                    "id" => $this->phpTag["tagID"],
                    "label" => "PHP",
                    "confidence" => 0.95,
                ],
                [
                    "id" => $this->helpTag["tagID"],
                    "label" => "Help",
                    "confidence" => 0.7, // Below 0.85 threshold
                ],
            ],
        ]);

        // Mock response for JavaScript programming (count limit test)
        $mockOpenAIClient->addMockResponse(json_encode("Need help with JavaScript programming"), [
            "labels" => [
                [
                    "id" => $this->javascriptTag["tagID"],
                    "label" => "JavaScript",
                    "confidence" => 0.9,
                ],
                [
                    "id" => $this->helpTag["tagID"],
                    "label" => "Help",
                    "confidence" => 0.8,
                ],
            ],
        ]);

        // Mock response with invalid tag IDs (hallucination test)
        $mockOpenAIClient->addMockResponse(json_encode("This will return invalid tag suggestions"), [
            "labels" => [
                [
                    "id" => -9999, // Invalid tag ID
                    "label" => "NonExistentTag",
                    "confidence" => 0.9,
                ],
                [
                    "id" => -8888, // Another invalid tag ID
                    "label" => "AnotherFakeTag",
                    "confidence" => 0.8,
                ],
            ],
        ]);

        // Mock response with no tags
        $mockOpenAIClient->addMockResponse(json_encode("This will return no tags"), [
            "labels" => [],
        ]);
    }

    /**
     * Assert that a specific tag suggestion was found in the results.
     *
     * @param array $results
     * @param int $tagID
     * @return void
     */
    public function assertTagSuggestionFound(array $results, int $tagID): void
    {
        $found = false;
        foreach ($results["tags"] ?? [] as $tag) {
            if ($tag["id"] === $tagID) {
                $found = true;
                $this->assertGreaterThan(0, $tag["confidence"], "Confidence should be greater than 0");
                break;
            }
        }
        $this->assertTrue($found, "Expected to find tag with ID {$tagID} but it was not found in the results.");
    }

    /**
     * Assert that a specific tag suggestion was NOT found in the results.
     *
     * @param array $results
     * @param int $tagID
     * @return void
     */
    public function assertTagSuggestionNotFound(array $results, int $tagID): void
    {
        $found = false;
        foreach ($results["tags"] ?? [] as $tag) {
            if ($tag["id"] === $tagID) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, "Expected NOT to find tag with ID {$tagID} but it was found in the results.");
    }
}
