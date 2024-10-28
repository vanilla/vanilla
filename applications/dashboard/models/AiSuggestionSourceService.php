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
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserMetaModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Activity\AiSuggestionsActivity;
use Vanilla\Dashboard\AiSuggestionModel;
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

    private array $reporterData = [];

    /**
     * AI Suggestion constructor.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @param UserMetaModel $userMetaModel
     * @param LongRunner $longRunner
     * @param \ActivityModel $activityModel
     * @param AiSuggestionModel $aiSuggestionModel
     * @param FormatService $formatService
     */
    public function __construct(
        private ConfigurationInterface $config,
        private OpenAIClient $openAIClient,
        private UserMetaModel $userMetaModel,
        private LongRunner $longRunner,
        private \ActivityModel $activityModel,
        private AiSuggestionModel $aiSuggestionModel,
        private FormatService $formatService
    ) {
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
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
        return FeatureFlagHelper::featureEnabled("aiFeatures") && FeatureFlagHelper::featureEnabled("AISuggestions");
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
     * Get Ai Suggestion User
     *
     * @return array
     */
    public static function getSuggestionUser(): array
    {
        $config = self::aiSuggestionConfigs();
        return Gdn::userModel()->getID($config["userID"], DATASET_TYPE_ARRAY);
    }

    /**
     * Check if AI suggestions are enabled for the user.
     *
     * @return bool
     */
    public function checkIfUserHasEnabledAiSuggestions(int $userID = null): bool
    {
        $userID = $userID ?? Gdn::session()->UserID;
        $suggestAnswers = $this->userMetaModel->getUserMeta($userID, "SuggestAnswers", true)["SuggestAnswers"];
        if (!$this->userMetaModel->hasUserAcceptedCookie($userID)) {
            $suggestAnswers = false;
        }

        return $suggestAnswers;
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
            $aiConfig["enabled"] &&
            FeatureFlagHelper::featureEnabled("customLayout.discussionThread");
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
        $discussion = $this->getDiscussionModel()->getID($recordID, DATASET_TYPE_ARRAY);
        $aiConfig = $this->aiSuggestionConfigs()["sources"];
        $suggestions = [];

        $keywords = $this->generateKeywords($discussion);
        /** @var AiSuggestionSourceInterface|null $source */
        foreach ($this->suggestionSources as $source) {
            try {
                //Skip this search path if it's not enabled in configs.
                $providerConfig = $aiConfig[$source->getName()] ?? [];
                if (($providerConfig["enabled"] ?? false) === false) {
                    continue;
                }
                $localSuggestions = $source->generateSuggestions($discussion, $keywords);
                $suggestions = array_merge($localSuggestions, $suggestions);
                yield new LongRunnerSuccessID($source->getName());
            } catch (Exception $e) {
                $this->logger->warning(
                    "Error generating suggestions for discussion {$discussion["DiscussionID"]}: {$e->getMessage()}"
                );
            }
        }

        try {
            $suggestions = $this->processResponses($discussion, $suggestions);
            $suggestionsMerged = $this->calculateTopSuggestions($suggestions, $discussion);
        } catch (Exception $e) {
            $suggestionsMerged = $suggestions;
            $this->logger->warning("Error Throws calculateTopSuggestions", ["exception" => $e]);
        }

        if (count($suggestionsMerged) === 0) {
            $this->logger->info("No found suggestion results.");
        }
        try {
            $this->aiSuggestionModel->saveSuggestions($recordID, $suggestionsMerged);
        } catch (Exception $e) {
            $this->logger->warning("Error Throws saving suggestions", ["exception" => $e]);
        }
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
        $discussionBody = $discussion["Name"];
        $discussionBody .= $this->formatService->renderPlainText($discussion["Body"], $discussion["Format"]);

        try {
            $prompt = OpenAIPrompt::create()->instruct(
                "You are a recommendation bot. Provide an array of 5 'keywords' related to the document provided."
            );
            $schema = Schema::parse(["keywords:a" => ["items" => ["type" => "string", "maxItems" => 5]]]);
            $prompt->addUserMessage("Recommend based on '$discussionBody'");
            $keywords = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $schema)["keywords"];
            $keywords = implode(",", $keywords);
        } catch (\Exception $e) {
            $this->logger->warning("Error generating Vanilla category suggestions for discussion", [
                "exception" => $e->getMessage(),
            ]);
            $keywords = $discussion["Name"];
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
        $result = [];
        $questionPlainText = $discussion["Name"] . "\n";
        $questionPlainText .= $this->formatService->renderPlainText($discussion["Body"], $discussion["Format"]);

        foreach ($potentialAnswers as $item) {
            try {
                $answerSchema = AiSuggestionSourceService::getAnswerSchema($item["summary"]);
                $prompt = AiSuggestionSourceService::getBasePrompt($item["summary"]);
                $prompt->addUserMessage($questionPlainText);
                $answer = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $answerSchema);
                if (!in_array($answer["answerSource"], [null, "null"], true)) {
                    $result[] = ["summary" => $answer["answerSource"]] + $item;
                }
            } catch (Exception $e) {
                $this->logger->warning("Error processing response", ["exception" => $e]);
                // If the body of the article is too long, abridge it to the provided limit, and try one more time to summarize.
                if (
                    str_contains($e->getMessage(), "maximum context length is") &&
                    preg_match("/[\d]+/", $e->getMessage(), $size)
                ) {
                    $extraSize = strlen($questionPlainText);
                    $summary = substr($item["summary"], 0, $size[0] - $extraSize);
                    $prompt = AiSuggestionSourceService::getBasePrompt($summary);
                    $prompt->addUserMessage($questionPlainText);
                    $answer = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $answerSchema);
                    if (!in_array($answer["answerSource"], [null, "null"], true)) {
                        $result[] = ["summary" => $answer["answerSource"]] + $item;
                    }
                }
            }
        }
        return $result;
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

        $useBrEnglishPrompt = $persona["useBrEnglish"] ? "Respond in British English." : "";

        $prompt = OpenAIPrompt::create()->instruct(
            <<<PROMPT
You are an answer bot, giving an answer to the user's question.
Answer the question using the text provided in a {$persona["toneOfVoice"]} tone of voice
for an audience that can understand the material with a {$persona["levelOfTech"]} level of technical knowledge.
$useBrEnglishPrompt

Answer the question by providing a quote from the source text that answers the user's question.
Only quote the relevant portion of the source text that directly answers the question. Do not include any irrelevant information.
Provide the quote as a value of the `answerSource` property in your response.

If the source text does not provide an answer to the question, set `answerSource` to `null`.

Source Text:
$sourceText
PROMPT
        );

        return $prompt;
    }

    /**
     * Get the schema for storing the answer response from OpenAI.
     *
     * @return Schema
     */
    public static function getAnswerSchema($suggestionSourceText): Schema
    {
        return Schema::parse(["answerSource:s|n"])->addValidator("answerSource", function (
            $quote,
            ValidationField $field
        ) use ($suggestionSourceText) {
            if (!str_contains(strtolower($suggestionSourceText), strtolower($quote))) {
                $field->addError("The provided answer does not exist within the source text.");
                return Invalid::value();
            }
            return true;
        });
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
     * @param bool $allSuggestions
     * @param array $aiSuggestionID
     *
     * @return array
     */
    public function createComments(int $discussionID, bool $allSuggestions, array $aiSuggestionID = []): array
    {
        $discussion = $this->getDiscussionModel()->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$this->suggestionEnabled() && "question" !== strtolower($discussion["Type"])) {
            return [];
        }
        $aiConfig = $this->aiSuggestionConfigs();
        $suggestionUserID = $aiConfig["userID"];
        if ($allSuggestions) {
            $suggestions = $this->aiSuggestionModel->getByDiscussionID($discussionID);
        } else {
            $suggestions = $this->aiSuggestionModel->getByIDs($aiSuggestionID);
        }
        $comments = [];

        foreach ($suggestions as $suggestion) {
            $comment = $this->createComment($suggestion, $discussion, $suggestionUserID);
            $this->aiSuggestionModel->update(
                ["commentID" => $comment["commentID"]],
                ["aiSuggestionID" => $suggestion["aiSuggestionID"]]
            );
            $comments[] = $comment;
        }
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
            "Body" => $this->formatService->renderHtml($suggestion["summary"], "text"),
            "Format" => HtmlFormat::FORMAT_KEY,
            "InsertUserID" => $suggestionUserID,
            "Attributes" => [
                "aiSuggestionID" => $suggestion["aiSuggestionID"],
            ],
        ];

        $commentID = $this->getCommentModel()->save($newComment);
        if (strtolower($discussion["Type"]) == "question") {
            $eventManager = Gdn::getContainer()->get(EventManager::class);
            $eventManager->fireFilter("commentModel_markAccepted", $commentID);
        }
        $comment = $this->getCommentModel()->getID($commentID, DATASET_TYPE_ARRAY);

        return $this->getCommentModel()->normalizeRow($comment);
    }

    /**
     * Delete comments based on accepted suggestions.
     *
     * @param int $discussionID
     * @param bool $allSuggestions
     * @param array $suggestionIDs
     *
     * @return bool
     */

    public function deleteComments(int $discussionID, bool $allSuggestions, array $suggestionIDs = []): bool
    {
        $discussion = $this->getDiscussionModel()->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$this->suggestionEnabled() && "question" !== strtolower($discussion["Type"])) {
            return false;
        }
        if ($allSuggestions) {
            $suggestions = $this->aiSuggestionModel->getByDiscussionID($discussionID);
        } else {
            $suggestions = $this->aiSuggestionModel->getByIDs($suggestionIDs);
        }
        foreach ($suggestions as &$suggestion) {
            $commentID = $suggestion["commentID"];
            $this->getCommentModel()->deleteID($commentID);
        }
        $this->aiSuggestionModel->update(["commentID" => null], ["aiSuggestionID" => $suggestionIDs]);
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

        $bodyPlainText = $this->formatService->renderPlainText($discussion["Body"], $discussion["Format"]);
        $prompt = OpenAIPrompt::create()->instruct(
            <<<PROMPT
You are given a list of articles. Return the top 3 articles sorted based on their relevance to the following discussion.
Return an array of article IDs sorted from most relevant to least relevant.

$bodyPlainText
PROMPT
        );

        foreach ($suggestions as $index => $suggestion) {
            $prompt->addUserMessage([
                "articleID" => $index,
                "articleContent" => $suggestion["summary"],
            ]);
        }

        $sortSchema = Schema::parse(["result:a" => ["items" => "integer", "maxItems" => 3]]);
        $sorts = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT35, $prompt, $sortSchema);

        $result = [];

        foreach ($sorts["result"] as $index) {
            if (isset($suggestions[$index])) {
                $result[] = $suggestions[$index];
            }
        }

        return $result;
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
        $where = ["discussionID" => $discussion["DiscussionID"]];
        if (!is_null($suggestionIDs)) {
            $where["aiSuggestionID"] = $suggestionIDs;
        }
        $this->aiSuggestionModel->update(["hidden" => $hide], $where);
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
        $this->getDiscussionModel()->setProperty($discussionID, "Attributes", dbencode($discussion["Attributes"]));
    }

    /**
     * Mark suggestions for this discussion as deleted, to not show them again.
     *
     * @param int $discussionID
     * @param bool $isDelete
     * @return void
     * @throws Exception
     */
    public function deleteSuggestions(int $discussionID, ?array $suggestionIDs = null, bool $isDelete = true)
    {
        $where = ["discussionID" => $discussionID];
        if (!is_null($suggestionIDs)) {
            $where["aiSuggestionID"] = $suggestionIDs;
        }
        $this->aiSuggestionModel->update(["isDeleted" => $isDelete], $where);
    }

    /**
     * Returns a schema for validating suggestion data.
     *
     * @return Schema
     */
    public static function getSuggestionSchema(): Schema
    {
        return Schema::parse([
            "aiSuggestionID:i",
            "format:s",
            "sourceIcon:s?",
            "type:s",
            "documentID:i?",
            "url:s",
            "title:s",
            "summary:s?",
            "hidden:b?",
            "commentID:i?",
        ]);
    }

    /**
     * @return DiscussionModel
     */
    private function getDiscussionModel(): DiscussionModel
    {
        return Gdn::getContainer()->get(DiscussionModel::class);
    }

    /**
     * @return CommentModel
     */
    private function getCommentModel(): CommentModel
    {
        return Gdn::getContainer()->get(CommentModel::class);
    }
}
