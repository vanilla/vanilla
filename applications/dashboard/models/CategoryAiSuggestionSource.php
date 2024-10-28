<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use CategoryModel;
use Gdn;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Controllers\Api\SearchApiController;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormChoicesInterface;
use Vanilla\Http\InternalClient;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Site\SiteSectionModel;

class CategoryAiSuggestionSource implements AiSuggestionSourceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var LongRunner */
    private LongRunner $longRunner;

    /**
     * Constructor
     *
     * @param InternalClient $api
     */
    public function __construct(private InternalClient $api)
    {
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
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
    public function getExclusionDropdownChoices(): ?FormChoicesInterface
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
    public function getExclusionLabel(): ?string
    {
        return t("Categories to Exclude from AI Answers");
    }

    /**
     * @inheritdoc
     */
    public function generateSuggestions(array $discussion, string $keywords): array
    {
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
            "queryOperator" => "or",
        ];
        $config = AiSuggestionSourceService::aiSuggestionConfigs();
        $providerConfig = $config["sources"][$this->getName()];
        if (count($providerConfig["exclusionIDs"] ?? []) > 0) {
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

        $searchResult = $this->api->get("/search", $params);
        $results = $searchResult->getBody();

        $formattedResult = [];
        foreach ($results as $result) {
            if ($result["recordID"] != $discussion["DiscussionID"]) {
                $formattedResult[] = [
                    "format" => "Vanilla",
                    "sourceIcon" => "search-discussion",
                    "type" => $result["type"],
                    "documentID" => $result["recordID"],
                    "url" => $result["url"],
                    "title" => $result["name"],
                    "summary" => $result["bodyPlainText"],
                    "hidden" => false,
                ];
            }
        }
        return $formattedResult;
    }
}
