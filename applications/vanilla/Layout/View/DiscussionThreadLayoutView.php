<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Garden\Web\Exception\ResponseException;
use Garden\Web\Redirect;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Widgets\DiscussionCommentEditorAsset;
use Vanilla\Forum\Widgets\DiscussionCommentsAsset;
use Vanilla\Forum\Widgets\DiscussionAttachmentsAsset;
use Vanilla\Forum\Widgets\DiscussionOriginalPostAsset;
use Vanilla\Forum\Widgets\DiscussionTagsAsset;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;
use Vanilla\Models\DiscussionJsonLD;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\BreadcrumbJsonLD;
use Vanilla\Web\PageHeadInterface;

/**
 * Legacy view type for discussion thread.
 */
class DiscussionThreadLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    private \DiscussionModel $discussionModel;
    private InternalClient $internalClient;
    private BreadcrumbModel $breadcrumbModel;
    private \Gdn_Request $request;
    private SiteSectionModel $siteSectionModel;

    /**
     * @param InternalClient $internalClient
     * @param \DiscussionModel $discussionModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param \Gdn_Request $request
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        InternalClient $internalClient,
        \DiscussionModel $discussionModel,
        BreadcrumbModel $breadcrumbModel,
        \Gdn_Request $request,
        SiteSectionModel $siteSectionModel
    ) {
        $this->internalClient = $internalClient;
        $this->discussionModel = $discussionModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->request = $request;
        $this->siteSectionModel = $siteSectionModel;
        $this->registerAssetClass(DiscussionCommentsAsset::class);
        $this->registerAssetClass(DiscussionAttachmentsAsset::class);
        $this->registerAssetClass(DiscussionOriginalPostAsset::class);
        $this->registerAssetClass(DiscussionCommentEditorAsset::class);
        $this->registerAssetClass(DiscussionTagsAsset::class);
    }

    public function getTemplateID(): string
    {
        return "discussionThread";
    }

    public function getParamInputSchema(): Schema
    {
        return Schema::parse(["discussionID:i", "commentID:i?", "page:i?" => ["default" => 1]]);
    }

    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $discussionID = $paramInput["discussionID"];
        $commentID = $paramInput["commentID"] ?? null;
        $commaSeparatedExpands = implode(",", $this->getExpands());
        $discussion = $this->internalClient
            ->get("/discussions/{$discussionID}?expand={$commaSeparatedExpands}")
            ->asData();
        $discussionTags = $discussion["tags"];
        $discussionTags = array_values(
            array_filter($discussionTags, function (array $tag) {
                return $tag["type"] === "User";
            })
        );
        $discussion["tags"] = $discussionTags;

        // Filter to only user tags.

        $discussionData = $discussion->getData();

        // Ensure we're in a valid site section, otherwise this will perform a redirect.
        $canonicalUrl = isset($paramInput["page"])
            ? $discussionData["canonicalUrl"] . "/p{$paramInput["page"]}"
            : $discussionData["canonicalUrl"];
        $redirectBase = $canonicalUrl;
        if ($commentID !== null) {
            $redirectBase .= "#Comment_" . $commentID;
        }
        $this->tryRedirectSiteSection($discussionData["categoryID"], $redirectBase);

        $pageHead->setSeoTitle($discussionData["name"], false);
        $pageHead->setSeoDescription(Gdn::formatService()->renderExcerpt($discussionData["body"], "html"));
        $crumbs = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($discussionData["categoryID"]));
        $pageHead->setSeoBreadcrumbs($crumbs);

        $pageHead->setCanonicalUrl($canonicalUrl);
        $pageHead->addOpenGraphTag("og:url", $canonicalUrl);
        if (isset($discussionData["image"])) {
            $pageHead->addOpenGraphTag("og:image", $discussionData["image"]["url"]);
        }
        $pageHead->addJsonLDItem(new DiscussionJsonLD(ArrayUtils::pascalCase($discussionData), $this->discussionModel));

        $breadcrumbs = $discussionData["breadcrumbs"] ?? [];
        $pageHead->addJsonLDItem(new BreadcrumbJsonLD($breadcrumbs));

        $expands = $this->getExpands();
        $result = array_merge($paramInput, [
            "discussion" => $discussion,
            "discussionApiParams" => [
                "expand" => $expands,
            ],
            "breadcrumbs" => $breadcrumbs,
            "tags" => $discussionTags,
        ]);

        return $result;
    }

    /**
     *
     * Define the expands necessary to provide the preloaded discussion, and for subsequent re-requests.
     * @return array<string>
     */
    public function getExpands(): array
    {
        return ["tags", "insertUser", "breadcrumbs", "reactions", "attachments", "reportMeta"];
    }

    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse(["discussion:o", "tags:a", "breadcrumbs:a", "discussionApiParams:o"]);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Comments Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussionThread";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Discussion/Index";
    }

    /**
     * Get the correct site section for a discussion.
     *
     * @param int $categoryID, string $discussionUrl
     */
    private function tryRedirectSiteSection(int $categoryID, string $discussionUrl): void
    {
        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();

        $siteSectionsForCategory = $this->siteSectionModel->getSiteSectionsForCategory($categoryID);

        $canonicalSiteSection = $siteSectionsForCategory[0] ?? null;
        if (!in_array($currentSiteSection, $siteSectionsForCategory) && $canonicalSiteSection !== null) {
            $currentPath = parse_url($discussionUrl, PHP_URL_PATH);
            $originalPath = str_replace("/", "\/", preg_quote($currentSiteSection->getBasePath()));
            $newPath = preg_replace("/^{$originalPath}/", $canonicalSiteSection->getBasePath(), $currentPath);

            // Redirect to discussion canonical
            throw new ResponseException(new Redirect($newPath, 302, false));
        }
    }
}
