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
use Vanilla\Forum\Widgets\PostCreateCommentAsset;
use Vanilla\Forum\Widgets\PostCommentThreadAsset;
use Vanilla\Forum\Widgets\PostAttachmentsAsset;
use Vanilla\Forum\Widgets\OriginalPostAsset;
use Vanilla\Forum\Widgets\PostMetaAsset;
use Vanilla\Forum\Widgets\PostTagsAsset;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;
use Vanilla\Models\DiscussionJsonLD;
use Vanilla\Models\DiscussionPlaceModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\BreadcrumbJsonLD;
use Vanilla\Web\PageHeadInterface;
use function PHPUnit\Framework\isEmpty;

/**
 * Legacy view type for discussion thread.
 */
class DiscussionLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    /**
     * DI
     */
    public function __construct(
        protected InternalClient $internalClient,
        protected \DiscussionModel $discussionModel,
        protected BreadcrumbModel $breadcrumbModel,
        protected \Gdn_Request $request,
        protected SiteSectionModel $siteSectionModel,
        protected \CategoryModel $categoryModel,
        protected DiscussionPlaceModel $discussionPlaceModel
    ) {
        $this->registerAssetClass(PostCommentThreadAsset::class);
        $this->registerAssetClass(PostAttachmentsAsset::class);
        $this->registerAssetClass(OriginalPostAsset::class);
        $this->registerAssetClass(PostCreateCommentAsset::class);
        $this->registerAssetClass(PostTagsAsset::class);
        $this->registerAssetClass(PostMetaAsset::class);
    }

    public function getTemplateID(): string
    {
        return "discussion";
    }

    public function getParamInputSchema(): Schema
    {
        return Schema::parse([
            "discussionID:i",
            "commentID:i?",
            "page:i" => ["default" => 1],
            "sort:s?" => [
                "enum" => ["-dateInserted", "dateInserted", "-score", "-" . ModelUtils::SORT_TRENDING, ""],
            ],
        ]);
    }

    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $discussionID = $paramInput["discussionID"];
        $commentID = $paramInput["commentID"] ?? null;
        $commaSeparatedExpands = implode(",", $this->getExpands());
        $discussion = $this->internalClient
            ->get("/discussions/{$discussionID}?expand={$commaSeparatedExpands}")
            ->asData();

        $this->discussionModel->tryRedirectFromDiscussion($discussion->getData());

        $discussionTags = $discussion["tags"] ?? [];

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

        $place = $this->discussionPlaceModel->getPlaceFragmentForApiRecord($discussion->getData());

        // Get all drafts, find if any match the current record ID and are of type comment.
        if (\Gdn::session()->checkPermission("session.valid")) {
            $allDrafts = $this->internalClient->get("/drafts?limit=500")->getBody();
        } else {
            $allDrafts = [];
        }
        $foundDraft = null;
        foreach ($allDrafts as $draft) {
            if ($draft["parentRecordID"] === $discussionID && $draft["recordType"] === "comment") {
                $foundDraft = $draft;
                break;
            }
        }

        $draftParams = [
            "serverDraftID" => "",
            "serverDraft" => [],
        ];

        if ($foundDraft !== null) {
            $draftParams = [
                "serverDraftID" => $foundDraft["draftID"],
                "serverDraft" => $foundDraft,
            ];
        }

        $result = array_merge(
            $paramInput,
            [
                "place" => $place,
                "categoryID" => $discussion["categoryID"],
                "category" => $this->categoryModel->getFragmentByID($discussion["categoryID"]),
                "discussion" => $discussion,
                "discussionApiParams" => [
                    "expand" => $expands,
                ],
                "breadcrumbs" => $breadcrumbs,
                "tags" => $discussionTags,
            ],
            $draftParams
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getContexts(array $resolvedParams): array
    {
        return [
            [
                "\$reactComponent" => "CreateCommentProvider",
                "\$reactProps" => [
                    "parentRecordType" => "discussion",
                    "parentRecordID" => $resolvedParams["discussionID"],
                ],
            ],
            [
                "\$reactComponent" => "DraftContextProvider",
                "\$reactProps" => [
                    // This means the draft context will be dealing with comment drafts in this view
                    "recordType" => "comment",
                    "parentRecordID" => $resolvedParams["discussionID"],
                    "serverDraft" => empty($resolvedParams["serverDraft"]) ? null : $resolvedParams["serverDraft"],
                    "serverDraftID" => $resolvedParams["serverDraftID"],
                ],
            ],
            [
                "\$reactComponent" => "PostPageContextProvider",
                "\$reactProps" => [
                    "discussion" => $resolvedParams["discussion"],
                    "discussionApiParams" => $resolvedParams["discussionApiParams"],
                    "initialPage" => $resolvedParams["page"],
                ],
            ],
        ];
    }

    /**
     *
     * Define the expands necessary to provide the preloaded discussion, and for subsequent re-requests.
     * @return array<string>
     */
    public function getExpands(): array
    {
        $expands = [
            "tags",
            "insertUser",
            "updateUser",
            "breadcrumbs",
            "reactions",
            "attachments",
            "reportMeta",
            "category",
            "postMeta",
            "permissions",
            "postMeta",
        ];
        $isBadgesEnabled = Gdn::addonManager()->isEnabled("badges", \Vanilla\Addon::TYPE_ADDON);
        $isWarningsEnabled = Gdn::addonManager()->isEnabled("warnings2", \Vanilla\Addon::TYPE_ADDON);
        $isSignaturesEnabled = \Gdn::addonManager()->isEnabled("Signatures", \Vanilla\Addon::TYPE_ADDON);

        // include needed expands if corresponding addons are enabled
        if ($isBadgesEnabled) {
            $expands[] = "insertUser.badges";
        }
        if ($isWarningsEnabled) {
            $expands[] = "warnings";
        }

        if ($isSignaturesEnabled) {
            $expands[] = "insertUser.signature";
        }

        return $expands;
    }

    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse([
            "discussion:o",
            "discussion/name:s?" => "Name of the discussion.",
            "tags:a",
            "breadcrumbs:a",
            "discussionApiParams:o",
            "serverDraftID:s?",
            "serverDraft:o?",
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Discussion Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussion";
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
     * @param int $categoryID , string $discussionUrl
     */
    private function tryRedirectSiteSection(int $categoryID, string $canonicalDiscussionUrl): void
    {
        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();

        $siteSectionsForCategory = $this->siteSectionModel->getSiteSectionsForCategory($categoryID);

        $canonicalSiteSection = $siteSectionsForCategory[0] ?? null;
        if (!in_array($currentSiteSection, $siteSectionsForCategory) && $canonicalSiteSection !== null) {
            // Redirect to discussion canonical
            throw new ResponseException(new Redirect($canonicalDiscussionUrl, 301));
        }
    }
}
