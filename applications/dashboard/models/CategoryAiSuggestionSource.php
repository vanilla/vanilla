<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use CategoryModel;
use DiscussionModel;
use Garden\Schema\Schema;
use Gdn;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserMetaModel;
use Vanilla\Controllers\Api\SearchApiController;
use Vanilla\Formatting\FormatService;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormChoicesInterface;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Site\SiteSectionModel;

class CategoryAiSuggestionSource implements AiSuggestionSourceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var OpenAIClient */
    private OpenAIClient $openAIClient;

    /** @var DiscussionModel  */
    private DiscussionModel $discussionModel;

    /** @var UserMetaModel  */
    private UserMetaModel $userMetaModel;

    /** @var SearchApiController */
    protected SearchApiController $searchApiController;

    /** @var LongRunner */
    private LongRunner $longRunner;

    /**
     * Constructor
     *
     * @param OpenAIClient $openAIClient
     * @param SearchApiController $searchApiController
     * @param DiscussionModel $discussionModel
     * @param UserMetaModel $userMetaModel
     */
    public function __construct(
        OpenAIClient $openAIClient,
        SearchApiController $searchApiController,
        DiscussionModel $discussionModel,
        UserMetaModel $userMetaModel
    ) {
        $this->openAIClient = $openAIClient;
        $this->searchApiController = $searchApiController;
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
        $this->discussionModel = $discussionModel;
        $this->userMetaModel = $userMetaModel;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "category";
    }

    /**
     * @inheritDoc
     */
    public function getExclusionDropdownChoices(): FormChoicesInterface
    {
        return new ApiFormChoices(
            "/api/v2/categories/search?query=%s&limit=30",
            "/api/v2/categories/%s",
            "categoryID",
            "name"
        );
    }

    /**
     * @inheritDoc
     */
    public function getToggleLabel(): string
    {
        return t("Community Discussion Categories");
    }

    /**
     * @inheritDoc
     */
    public function getExclusionLabel(): string
    {
        return t("Categories to Exclude from AI Answers");
    }

    /**
     * @inheritdoc
     */
    public function generateSuggestions(array $discussion): array
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
            $result = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT4, $prompt, $schema)["properties"];
            $keywords = $result["keywords"];
        } catch (\Exception $e) {
            $this->logger->warning(
                "Error generating Vanilla category suggestions for discussion {$discussion["DiscussionID"]}: {$e->getMessage()}"
            );
            $keywords = $question;
        }
        $siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $siteSection = $siteSectionModel->getCurrentSiteSection();
        $currentLocale = $siteSection->getContentLocale();
        $params = [
            "query" => $keywords,
            "locale" => $currentLocale,
            "page" => 1,
            "expand" => ["excerpt", "image"],
            "expandBody" => true,
            "limit" => 3,
            "recordTypes" => ["discussion", "comment"],
        ];
        $config = AiSuggestionSourceService::aiSuggestionConfigs();
        $providerConfig = $config["sources"][$this->getName()];
        if (count($providerConfig["exclusionIDs"]) > 0) {
            $categoryModel = GDN::getContainer()->get(CategoryModel::class);
            $categoryIDs = [];
            foreach ($categoryModel->getSearchCategoryIDs() as $categoryID) {
                if (in_array($categoryID, $providerConfig["exclusionIDs"])) {
                    continue;
                }
                $categoryIDs[] = $categoryID;
            }
            if ($categoryIDs > 0) {
                $params["categoryIDs"] = $categoryIDs;
            }
        }

        $searchResult = $this->searchApiController->index($params);
        $results = $searchResult->getData()->getResultItems();

        $toneOfVoice = $this->userMetaModel->getUserMeta($config["userID"], "toneOfVoice", "Friendly");
        $levelOfTech = $this->userMetaModel->getUserMeta($config["userID"], "levelOfTech", "Layman's Terms");

        $formattedResult = [];
        $summarySchema = Schema::parse(["properties:o" => Schema::parse(["summary:s"])]);
        foreach ($results as $result) {
            $prompt = OpenAIPrompt::create()->instruct(
                "You are an answer bot, giving an answer to the following question: $question.  With the question as the \"title\", answer the question using the text provided in $toneOfVoice tone of voice for an audience that can understand the material in $levelOfTech."
            );
            $prompt->addUserMessage($result->getBody());
            $summary = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT4, $prompt, $summarySchema)["properties"];
            if ($result["recordID"] != $discussion["DiscussionID"]) {
                $formattedResult[] = [
                    "format" => "Vanilla",
                    "type" => $result["type"],
                    "id" => $result["recordID"],
                    "url" => $result["url"],
                    "title" => $result["name"],
                    "summary" => $summary["summary"],
                    "hidden" => false,
                ];
            }
        }
        return $formattedResult;
    }
}
