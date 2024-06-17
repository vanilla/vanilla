<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use CommentModel;
use DiscussionModel;
use Exception;
use Garden\EventManager;
use Garden\Schema\Schema;
use Gdn;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserMetaModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Web\SystemCallableInterface;

class AiSuggestionSourceService implements LoggerAwareInterface, SystemCallableInterface
{
    use LoggerAwareTrait;

    /** @var AiSuggestionSourceInterface[] */
    protected array $suggestionSources;

    private const CONFIG_KEY = "aiSuggestions";
    private const MAX_SUGGESTIONS = 3;

    protected string $domain;

    /** @var ConfigurationInterface */
    protected ConfigurationInterface $config;

    /** @var OpenAIClient */
    private OpenAIClient $openAIClient;

    /** @var DiscussionModel  */
    private DiscussionModel $discussionModel;

    /** @var CommentModel  */
    private CommentModel $commentModel;

    /** @var UserMetaModel  */
    private UserMetaModel $userMetaModel;

    private array $reporterData = [];

    /** @var LongRunner */
    private LongRunner $longRunner;

    /**
     * AI Suggestion constructor.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     * @param UserMetaModel $userMetaModel
     * @param LongRunner $longRunner
     */
    public function __construct(
        ConfigurationInterface $config,
        OpenAIClient $openAIClient,
        DiscussionModel $discussionModel,
        CommentModel $commentModel,
        UserMetaModel $userMetaModel,
        LongRunner $longRunner
    ) {
        $this->config = $config;
        $this->openAIClient = $openAIClient;
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->userMetaModel = $userMetaModel;
        $this->longRunner = $longRunner;
    }

    /**
     * Add an AI suggestion source to the service.
     *
     * @param AiSuggestionSourceInterface $suggestionSource
     * @return void
     */
    public function registerSuggestionSource(AiSuggestionSourceInterface $suggestionSource): void
    {
        $this->suggestionSources[] = $suggestionSource;
    }

    public static function getSystemCallableMethods(): array
    {
        return ["generateSuggestions"];
    }

    /**
     * Check if AI suggestions are enabled.
     *
     * @return bool
     */
    private function aiSuggestionFeatureEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled("AISuggestions");
    }

    /**
     * Get AI suggestions configs.
     *
     * @return array
     */
    public static function aiSuggestionConfigs(): array
    {
        return Gdn::config()->get(self::CONFIG_KEY, [
            "enabled" => false,
            "userID" => 0,
            "sources" => ["category" => ["enabled" => false, "exclusionIDs" => [0]]],
        ]);
    }

    /**
     * Check if AI suggestions are enabled.
     *
     * @return bool
     */
    private function checkIfUserHasEnabledAiSuggestions(): bool
    {
        return $this->userMetaModel->getUserMeta(Gdn::session()->UserID, "SuggestAnswers", true)["SuggestAnswers"];
    }

    /**
     * Check if AI suggestions are enabled.
     *
     * @return bool
     */
    public function suggestionEnabled(): bool
    {
        $aiConfig = $this->aiSuggestionConfigs();
        return $this->aiSuggestionFeatureEnabled() &&
            $this->checkIfUserHasEnabledAiSuggestions() === true &&
            $aiConfig["enabled"];
    }

    /**
     * Start the process to generate suggestions by calling longRunner.
     *
     * @param int $recordID
     * @param array $discussion
     * @return void
     */
    public function createAttachment(int $recordID, array $discussion): void
    {
        if (!$this->suggestionEnabled() || "question" !== strtolower($discussion["Type"])) {
            return;
        }

        $action = new LongRunnerAction(self::class, "generateSuggestions", [$recordID, $discussion]);
        $this->longRunner->runDeferred($action);
    }

    /**
     * Generate suggestions for a discussion LongRunner.
     *
     * @param int $recordID
     * @param array $discussion
     * @return Generator
     */
    public function generateSuggestions(int $recordID, array $discussion): Generator
    {
        $aiConfig = $this->aiSuggestionConfigs()["sources"];
        $suggestions = [];
        try {
            /** @var AiSuggestionSourceInterface|null $source */
            foreach ($this->suggestionSources as $source) {
                //Skip this search path if it's not enabled in configs.
                $providerConfig = $aiConfig[$source->getName()] ?? [];
                if (($providerConfig["enabled"] ?? false) === false) {
                    continue;
                }
                $localSuggestions = $source->generateSuggestions($discussion);
                $suggestions = array_merge($localSuggestions, $suggestions);
                yield new LongRunnerSuccessID($source->getName());
            }
        } catch (Exception $e) {
            $this->logger->warning(
                "Error generating suggestions for discussion {$discussion["DiscussionID"]}: {$e->getMessage()}"
            );
        }

        try {
            $suggestionsMerged = $this->calculateTopSuggestions($suggestions, $discussion);
        } catch (Exception $e) {
            $suggestionsMerged = $suggestions;
        }
        if (!isset($discussion["Attributes"]["suggestions"])) {
            $discussion["Attributes"]["suggestions"] = [];
        }
        $discussion["Attributes"]["visibleSuggestions"] = true;
        $discussion["Attributes"]["suggestions"] = array_merge(
            $discussion["Attributes"]["suggestions"],
            $suggestionsMerged
        );
        $this->discussionModel->setProperty($recordID, "Attributes", dbencode($discussion["Attributes"]));

        return LongRunner::FINISHED;
    }

    /**
     * Generate one or multiple comments based on accepted suggestions.
     *
     * @param int $discussionID
     * @param array $suggestionIndex
     *
     * @return array
     */
    public function createComments(int $discussionID, array $suggestionIndex): array
    {
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$this->suggestionEnabled() && "question" !== strtolower($discussion["Type"])) {
            return [];
        }
        $aiConfig = $this->aiSuggestionConfigs();
        $suggestionUserID = $aiConfig["userID"];
        $attributes = $discussion["Attributes"] ?? [];
        $suggestions = $attributes["suggestions"] ?? [];
        $comments = [];

        foreach ($suggestionIndex as $index) {
            if (($suggestions[$index] ?? false) && ($suggestions[$index]["commentID"] ?? null) === null) {
                $comment = $this->createComment($suggestions[$index]["summary"], $discussion, $suggestionUserID);
                $suggestions[$index]["commentID"] = $comment["commentID"];
                $comments[] = $comment;
            }
        }
        $attributes["suggestions"] = $suggestions;
        $this->discussionModel->setProperty($discussionID, "Attributes", dbencode($attributes));
        return $comments;
    }

    /**
     * Create 1 comment from the suggestion.
     *
     * @param string $body
     * @param array $discussion
     * @param int $suggestionUserID
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function createComment(string $body, array $discussion, int $suggestionUserID): array
    {
        $newComment = [
            "DiscussionID" => $discussion["DiscussionID"],
            "Body" => Gdn::formatService()->renderHtml($body, "text"),
            "Format" => HtmlFormat::FORMAT_KEY,
            "InsertUserID" => $suggestionUserID,
        ];

        $commentID = $this->commentModel->save($newComment);
        if (strtolower($discussion["Type"]) == "question") {
            $eventManager = Gdn::getContainer()->get(EventManager::class);
            $eventManager->fireFilter("commentModel_markAccepted", $commentID);
        }
        $comment = $this->commentModel->getID($commentID, DATASET_TYPE_ARRAY);

        return $this->commentModel->normalizeRow($comment);
    }

    /**
     * Delete comments based on accepted suggestions.
     *
     * @param int $discussionID
     * @param array $suggestionIndex
     *
     * @return bool
     */

    public function deleteComments(int $discussionID, array $suggestionIndex): bool
    {
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$this->suggestionEnabled() && "question" !== strtolower($discussion["Type"])) {
            return false;
        }
        $attributes = $discussion["Attributes"] ?? [];
        $suggestions = $attributes["suggestions"] ?? [];
        foreach ($suggestionIndex as $index) {
            if ($suggestions[$index] ?? false) {
                $commentID = $suggestions[$index]["commentID"];
                $this->commentModel->deleteID($commentID);
                $suggestions[$index]["commentID"] = null;
            }
        }
        $attributes["suggestions"] = $suggestions;
        $this->discussionModel->setProperty($discussionID, "Attributes", dbencode($attributes));

        return true;
    }

    /**
     * Calculate suggestions rating.
     *
     * @param array $suggestions Array of suggestions to check
     * @param array $discussion Record from the discussion table.
     * @return array
     */
    private function calculateTopSuggestions(array $suggestions, array $discussion): array
    {
        $suggestions = array_values($suggestions);

        $formatService = Gdn::getContainer()->get(FormatService::class);
        $bodyPlainText = $formatService->renderPlainText($discussion["Body"], $discussion["Format"]);
        $prompt = OpenAIPrompt::create()->instruct(
            <<<PROMPT
You are given a list of articles. Sort the articles from most to least relevant based on their relevance to the following discussion.
Return the results as an array of objects. Each object contains the articleID and its sort value.

$bodyPlainText
PROMPT
        );

        $sortSchema = Schema::parse([
            ":a" => [
                "items" => Schema::parse(["articleID:i", "sortValue:i"]),
            ],
        ]);
        foreach ($suggestions as $index => $suggestion) {
            $prompt->addUserMessage([
                "articleID" => $index,
                "articleContent" => $suggestion["summary"],
            ]);
        }

        $sortedSuggestions = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT4, $prompt, $sortSchema);
        $sortedSuggestions = array_column($sortedSuggestions, "sortValue", "articleID");
        uksort($suggestions, function ($item1, $item2) use ($sortedSuggestions) {
            return $sortedSuggestions[$item1] <=> $sortedSuggestions[$item2];
        });
        return array_values($suggestions);
    }

    /**
     * Get the schema for updating and retrieving settings.
     *
     * @return Schema
     */
    public function getSettingsSchema(): Schema
    {
        $schema = [];
        foreach ($this->suggestionSources as $suggestionSource) {
            $schema[$suggestionSource->getName() . "?"] = Schema::parse([
                "enabled:b",
                "exclusionIDs:a" => ["items" => ["type" => "integer"]],
            ]);
        }
        return Schema::parse($schema);
    }

    /**
     * Get the schema for rendering fields for the dashboard.
     *
     * @return Schema
     */
    public function getSourcesSchema(): Schema
    {
        $schemaArray = [];
        foreach ($this->suggestionSources as $suggestionSource) {
            $schemaArray[$suggestionSource->getName() . "?"] = Schema::parse([
                "enabled:b" => [
                    "default" => false,
                    "x-control" => SchemaForm::checkBox(
                        new FormOptions($suggestionSource->getToggleLabel()),
                        labelType: "none"
                    ),
                ],
                "exclusionIDs" => [
                    "default" => null,
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions($suggestionSource->getExclusionLabel()),
                        $suggestionSource->getExclusionDropdownChoices(),
                        multiple: true
                    ),
                ],
            ]);
        }
        return Schema::parse($schemaArray);
    }

    /**
     * Toggle display state of suggestions for a discussion.
     *
     * @param array $discussion The discussion record.
     * @param array|null $suggestionIDs Array of suggestion IDs or null to toggle all suggestions.
     * @param bool $hide Whether to hide or show the suggestions.
     * @return void
     */
    public function toggleSuggestions(array $discussion, ?array $suggestionIDs = null, bool $hide = true): void
    {
        $suggestions = $discussion["Attributes"]["suggestions"] ?? [];

        foreach ($suggestions as $index => &$suggestion) {
            if (is_null($suggestionIDs) || in_array($index, $suggestionIDs)) {
                $suggestion["hidden"] = $hide;
            }
        }

        $discussion["Attributes"]["suggestions"] = $suggestions;
        $discussionID = $discussion["DiscussionID"];
        $this->discussionModel->setProperty($discussionID, "Attributes", dbencode($discussion["Attributes"]));
    }

    /**
     * Update visible suggestions segment for the discussion.
     *
     * @param array $discussion
     * @param bool $visible
     *
     * @return void
     */
    public function updateVisibleSuggestions(array $discussion, bool $visible = true): void
    {
        $discussion["Attributes"]["visibleSuggestions"] = $visible;
        $discussionID = $discussion["DiscussionID"];
        $this->discussionModel->setProperty($discussionID, "Attributes", dbencode($discussion["Attributes"]));
    }
}
