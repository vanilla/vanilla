<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use UsersApiController;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use TagModel;

/**
 * Class for adding extra site meta related to posting settings.
 */
class PostingSiteMetaExtra extends \Vanilla\Models\SiteMetaExtra
{
    /**
     * DI.
     */
    public function __construct(
        private \Vanilla\Contracts\ConfigurationInterface $config,
        private EscalationModel $escalationModel,
        private TagModel $tagModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getValue(): array
    {
        $meta = $this->getPostingSiteMetaExtra();
        return $meta;
    }

    /**
     * Get the posting settings values to add to the site meta.
     *
     * @return array
     */
    public function getPostingSiteMetaExtra(): array
    {
        $autosaveEnabled = $this->config->get("Vanilla.Drafts.Autosave", true);
        $trustedDomains = $this->config->get("Garden.TrustedDomains");
        $disableUrlEmbeds = $this->config->get("Garden.Format.DisableUrlEmbeds");
        $minLength = $this->config->get("Vanilla.Comment.MinLength");
        $postTypes = array_values(array_filter(array_column(\DiscussionModel::discussionTypes(), "apiType")));
        $postTypeModel = \Gdn::getContainer()->get(PostTypeModel::class);
        $availablePostTypes = $postTypeModel->getAvailablePostTypes();
        $postTypesMap = array_column($availablePostTypes, null, index_key: "postTypeID");

        $mentionsConfig = $this->config->get("Garden.Format.Mentions", UsersApiController::AT_MENTION_GLOBAL);
        $mentionsEnabled = $mentionsConfig !== UsersApiController::AT_MENTION_DISABLED;

        return [
            "community" => [
                "drafts" => [
                    "autosave" => $autosaveEnabled,
                ],
            ],
            "triage" => [
                "enabled" => $this->config->get("triage.enabled", false),
            ],
            "trustedDomains" => $trustedDomains,
            "disableUrlEmbeds" => $disableUrlEmbeds,
            "postTypesMap" => $postTypesMap,
            "postTypes" => $postTypes,
            "escalation" => [
                "statuses" => $this->escalationModel->getStatuses(),
            ],
            "posting" => [
                "minLength" => $minLength,
                "titleMaxLength" => \DiscussionModel::getPostTitleMaxLength(),
            ],
            "tagging" => [
                "enabled" => $this->tagModel->discussionTaggingEnabled(),
                "scopedTaggingEnabled" => $this->tagModel->scopedTaggingEnabled(),
            ],
            "mentions" => [
                "enabled" => $mentionsEnabled,
            ],
        ];
    }
}
