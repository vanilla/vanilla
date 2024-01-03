<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use DiscussionModel;
use Garden\Web\Exception\ResponseException;
use Garden\Web\Redirect;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout discussion list page
 *
 * {@link DiscussionThreadLayoutView}
 */
class DiscussionThreadPageController extends PageDispatchController
{
    private InternalClient $internalClient;
    private SiteSectionModel $siteSectionModel;
    private DiscussionModel $discussionModel;
    private array $postLayoutViewTypes = [
        "discussion" => "discussionThread",
        // TODO: Perhaps these should be registered elsewhere?
        "idea" => "ideaThread",
        "question" => "questionThread",
    ];

    /**
     * @param InternalClient $internalClient
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        InternalClient $internalClient,
        SiteSectionModel $siteSectionModel,
        DiscussionModel $discussionModel
    ) {
        $this->internalClient = $internalClient;
        $this->siteSectionModel = $siteSectionModel;
        $this->discussionModel = $discussionModel;
    }

    public function index(string $path)
    {
        $discussionID = StringUtils::parseIDFromPath($path, "\/");
        $pageNumber = StringUtils::parsePageNumberFromPath($path);

        $page = $this->usePage(LayoutPage::class);
        $page->setSeoRequired(false);

        if ($discussionID) {
            try {
                // Fetch the discussion to get the proper site section.
                $discussionData = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
                $siteSection = $this->getSiteSection($discussionData);
                $page->preloadLayout(
                    new LayoutQuery("discussionThread", "discussion", $discussionID, [
                        "discussionID" => $discussionID,
                        "locale" => $siteSection["contentLocale"],
                        "siteSectionID" => $siteSection["sectionID"],
                        "page" => $pageNumber,
                    ])
                );
            } catch (ResponseException $ex) {
                $location = $ex->getResponse()->getMeta("HTTP_LOCATION");
                redirectTo($location, 302, false);
            }
        }

        return $page->render();
    }

    /**
     * Get the correct site section for a discussion.
     *
     * @param array $discussionData
     * @return array
     */
    private function getSiteSection(array $discussionData): array
    {
        $siteSection = $this->siteSectionModel->getCurrentSiteSection();

        $allSiteSections = $this->siteSectionModel->getAll();
        foreach ($allSiteSections as $section) {
            $basePath = $section->getBasePath();
            // If we're in the root site section, we need to check if the discussion belongs to another site section.
            // If it doesn't, use the root.
            if (empty($basePath)) {
                $urlParts = parse_url($discussionData["CanonicalUrl"]);
                if (stringBeginsWith($urlParts["path"], "/discussion")) {
                    return SiteSectionSchema::toArray($siteSection);
                }
            }

            if (strpos($discussionData["CanonicalUrl"], url("/", true)) === 0 && !empty($basePath)) {
                $canonicalSiteSection = $section;
                break;
            }
        }

        if ($siteSection->getSectionID() !== $canonicalSiteSection->getSectionID()) {
            // It's possible we're still in a canonical site section, because the discussion belongs to more than one.
            $this->checkMultipleCanonicals($siteSection, $discussionData);
        }

        return SiteSectionSchema::toArray($siteSection);
    }

    /**
     * Check that a site section is canonical for a discussion. Redirect if it isn't.
     *
     * @param SiteSectionInterface $currentSiteSection
     * @param array $discussionData
     * @return void
     * @throws ResponseException
     */
    private function checkMultipleCanonicals(SiteSectionInterface $currentSiteSection, array $discussionData): void
    {
        $currentSectionCategories = $currentSiteSection->getAttributes()["allCategories"] ?? [];
        if (!in_array($discussionData["CategoryID"], $currentSectionCategories)) {
            throw new ResponseException(new Redirect($discussionData["CanonicalUrl"], 302, false));
        }
    }
}
