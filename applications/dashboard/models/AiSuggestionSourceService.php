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
use Garden\Utils\ArrayUtils;
use Gdn;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserMetaModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Activity\AiSuggestionsActivity;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
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

    private \ActivityModel $activityModel;

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
        LongRunner $longRunner,
        \ActivityModel $activityModel
    ) {
        $this->config = $config;
        $this->openAIClient = $openAIClient;
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->userMetaModel = $userMetaModel;
        $this->longRunner = $longRunner;
        $this->activityModel = $activityModel;
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
     * Check if AI suggestions are enabled for the user.
     *
     * @return bool
     */
    private function checkIfUserHasEnabledAiSuggestions(): bool
    {
        return $this->userMetaModel->getUserMeta(Gdn::session()->UserID, "SuggestAnswers", true)["SuggestAnswers"];
    }

    /**
     * Check if AI suggestions are enabled for the user and in the app.
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
     * Check if AI suggestions feature is enabled in the app.
     *
     * @return bool
     */
    public function suggestionFeatureEnabled(): bool
    {
        $aiConfig = $this->aiSuggestionConfigs();
        return $this->aiSuggestionFeatureEnabled() && $aiConfig["enabled"];
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

        $action = new LongRunnerAction(self::class, "generateSuggestions", [$recordID]);
        $this->longRunner->runDeferred($action);
    }

    /**
     * Generate suggestions for a discussion LongRunner.
     *
     * @param int $recordID
     * @return Generator
     */
    public function generateSuggestions(int $recordID): Generator
    {
        $discussion = $this->discussionModel->getID($recordID, DATASET_TYPE_ARRAY);
        $aiConfig = $this->aiSuggestionConfigs()["sources"];
        $suggestions = [];
        try {
            $keywords = $this->generateKeywords($discussion);
            /** @var AiSuggestionSourceInterface|null $source */
            foreach ($this->suggestionSources as $source) {
                //Skip this search path if it's not enabled in configs.
                $providerConfig = $aiConfig[$source->getName()] ?? [];
                if (($providerConfig["enabled"] ?? false) === false) {
                    continue;
                }
                $localSuggestions = $source->generateSuggestions($discussion, $keywords);
                $suggestions = array_merge($localSuggestions, $suggestions);
                yield new LongRunnerSuccessID($source->getName());
            }
        } catch (Exception $e) {
            $this->logger->warning(
                "Error generating suggestions for discussion {$discussion["DiscussionID"]}: {$e->getMessage()}"
            );
        }

        try {
            $suggestions = $this->processResponses($discussion, $suggestions);
            $suggestionsMerged = $this->calculateTopSuggestions($suggestions, $discussion);
        } catch (Exception $e) {
            $suggestionsMerged = $suggestions;
        }
        $discussion["Attributes"]["suggestions"] = $suggestionsMerged;
        $this->discussionModel->setProperty($recordID, "Attributes", dbencode($discussion["Attributes"]));

        if (!empty($suggestionsMerged)) {
            $this->notifyAiSuggestions($discussion);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Generate keywords from a discussion.
     *
     * @param array $discussion
     * @return string
     */
    public function generateKeywords(array $discussion): string
    {
        $this->discussionModel->formatField($discussion, "Body", $discussion["Format"]);

        $discussionBody = $discussion["Name"] . $discussion["Body"];
        $question = $discussion["Body"];
        try {
            $prompt = OpenAIPrompt::create()->instruct(
                "You are a recommendation bot, giving comma separated list of 5 'keywords' related to a list of documents provided."
            );
            $schema = Schema::parse(["properties:o" => Schema::parse(["keywords:s"])]);
            $prompt->addUserMessage("Recommend based on '$discussionBody'");
            $result = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $schema)["properties"];
            $keywords = $result["keywords"];
        } catch (\Exception $e) {
            $this->logger->warning(
                "Error generating Vanilla category suggestions for discussion {$discussion["DiscussionID"]}: {$e->getMessage()}"
            );
            $keywords = $question;
        }
        return $keywords;
    }

    /**
     * Turn returned responses into answers to the main question
     *
     * @param array $discussion
     * @param array $potentialAnswers
     * @return array
     */
    public function processResponses(array $discussion, array $potentialAnswers): array
    {
        $answerSchema = AiSuggestionSourceService::getAnswerSchema();

        foreach ($potentialAnswers as &$article) {
            $prompt = AiSuggestionSourceService::getBasePrompt($article["summary"]);
            $prompt->addUserMessage($discussion["Body"]);
            $answer = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $answerSchema);
            if ($answer["hasAnswer"]) {
                $article["summary"] = $answer["answer"];
            }
        }
        return $potentialAnswers;
    }

    /**
     * Get the base prompt for querying OpenAI for an answer to a discussion using various sources.
     *
     * @param string $sourceText
     * @return OpenAIPrompt
     */
    public static function getBasePrompt(string $sourceText): OpenAIPrompt
    {
        $config = AiSuggestionSourceService::aiSuggestionConfigs();

        $persona = Gdn::userMetaModel()->getUserMeta($config["userID"], "aiAssistant.%", [], "aiAssistant.");
        $persona = $persona + ["toneOfVoice" => "friendly", "levelOfTech" => "layman", "useBrEnglish" => false];

        $prompt = OpenAIPrompt::create()->instruct(
            <<<PROMPT
You are an answer bot, giving an answer to the user's question.
Answer the question using the text provided in a {$persona["toneOfVoice"]} tone of voice
for an audience that can understand the material with a {$persona["levelOfTech"]} level of technical knowledge.
PROMPT
        );
        if ($persona["useBrEnglish"]) {
            $prompt->instruct("Respond in British English.");
        }
        $prompt->instruct(
            "Use ONLY the content derived from the following source text. If the answer cannot be derived from the source text alone, respond with a `hasAnswer` field with a value of `false`."
        );
        $prompt->instruct("Source Text:\n$sourceText");

        return $prompt;
    }

    /**
     * Get the schema for storing the answer response from OpenAI.
     *
     * @return Schema
     */
    public static function getAnswerSchema(): Schema
    {
        return Schema::parse(["answer:s", "hasAnswer:b?" => ["default" => true]]);
    }

    /**
     * @param array $discussion
     * @return void
     * @throws Exception
     */
    private function notifyAiSuggestions(array $discussion): void
    {
        $assistantUserID = Gdn::userModel()
            ->getWhere(["UserID" => self::aiSuggestionConfigs()["userID"] ?? null])
            ->value("UserID");

        if (empty($assistantUserID)) {
            return;
        }

        $activity = [
            "ActivityType" => "AiSuggestions",
            "ActivityUserID" => $assistantUserID,
            "NotifyUserID" => $discussion["InsertUserID"],
            "HeadlineFormat" => AiSuggestionsActivity::getProfileHeadline(),
            "RecordType" => "Discussion",
            "RecordID" => $discussion["DiscussionID"],
            "Route" => DiscussionModel::discussionUrl($discussion),
        ];

        $this->activityModel->save($activity, "AiSuggestions");
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
                $comment = $this->createComment($suggestions[$index], $discussion, $suggestionUserID);
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
     * @param array $suggestion
     * @param array $discussion
     * @param int $suggestionUserID
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function createComment(array $suggestion, array $discussion, int $suggestionUserID): array
    {
        $newComment = [
            "DiscussionID" => $discussion["DiscussionID"],
            "Body" => Gdn::formatService()->renderHtml($suggestion["summary"], "text"),
            "Format" => HtmlFormat::FORMAT_KEY,
            "InsertUserID" => $suggestionUserID,
            "Attributes" => [
                "suggestion" => ArrayUtils::pluck($suggestion, ["sourceIcon", "title", "url", "format", "type"]),
            ],
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
Return the top 3 results as an array of objects. Each object contains the articleID and its sort value.

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

        $sortedSuggestions = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $sortSchema);
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
                "enabled:b?" => ["default" => false],
                "exclusionIDs:a?" => ["items" => ["type" => "integer"]],
            ]);
        }
        return Schema::parse($schema);
    }

    /**
     * Get a catalog of all the available sources for rendering fields on the dashboard.
     *
     * @return array
     */
    public function getSourcesCatalog(): array
    {
        $catalog = [];
        foreach ($this->suggestionSources as $suggestionSource) {
            $catalog[$suggestionSource->getName()] = [
                "enabledLabel" => $suggestionSource->getToggleLabel(),
                "exclusionLabel" => $suggestionSource->getExclusionLabel(),
                "exclusionChoices" => $suggestionSource->getExclusionDropdownChoices()?->getChoices(),
            ];
        }
        return $catalog;
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
        $discussion["Attributes"]["showSuggestions"] = $visible;
        $discussionID = $discussion["DiscussionID"];
        $this->discussionModel->setProperty($discussionID, "Attributes", dbencode($discussion["Attributes"]));
    }

    /**
     * Returns a schema for validating suggestion data.
     *
     * @return Schema
     */
    public static function getSuggestionSchema(): Schema
    {
        return Schema::parse([
            "format:s",
            "sourceIcon:s?",
            "type:s",
            "id:i?",
            "url:s",
            "title:s",
            "summary:s?",
            "hidden:b?",
            "commentID:i?",
        ]);
    }
}
