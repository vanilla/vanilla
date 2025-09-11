<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Formatting\FormatService;
use Vanilla\Utility\KeywordUtils;
use Vanilla\Web\TwigRenderTrait;

/**
 * A premoderation handler that requires approval for certain categories.
 */
class ApprovalPremoderator implements PremoderationHandlerInterface
{
    use TwigRenderTrait;

    const CONF_PREMODERATED_CATEGORY_IDS = "premoderation.categoryIDs";
    const CONF_PREMODERATED_DISCUSSIONS_ENABLED = "premoderation.discussions";
    const CONF_PREMODERATED_COMMENTS_ENABLED = "premoderation.comments";
    const CONF_PREMODERATED_KEYWORDS = "premoderation.keywords";

    /**
     * DI.
     */
    public function __construct(
        private \Gdn_Session $session,
        private ConfigurationInterface $config,
        private \UserModel $userModel,
        private FormatService $formatService
    ) {
    }

    /**
     * Perform some migration of legacy config values (prior to 2024.013).
     *
     * @param \Gdn_Configuration $config
     * @return void
     */
    public static function structure(\Gdn_Configuration $config): void
    {
        $config->touch(
            ApprovalPremoderator::CONF_PREMODERATED_DISCUSSIONS_ENABLED,
            $config->get("PreModeratedCategory.Discussions", false)
        );
        $config->touch(
            ApprovalPremoderator::CONF_PREMODERATED_COMMENTS_ENABLED,
            $config->get("PreModeratedCategory.Comments", false)
        );
        $config->touch(ApprovalPremoderator::CONF_PREMODERATED_KEYWORDS, $config->get("KeywordBlocker.Words", ""));
        $legacyPremodIDs = $config->get("PreModeratedCategory.IDs", "");
        $modernPremodIDs = $config->get(ApprovalPremoderator::CONF_PREMODERATED_CATEGORY_IDS, null);
        if ($modernPremodIDs === null && !empty($legacyPremodIDs)) {
            // Validate and split the legacy IDs.
            if (is_string($legacyPremodIDs)) {
                $legacyPremodIDs = array_filter(explodeTrim(",", $legacyPremodIDs));
            }
            $config->set(ApprovalPremoderator::CONF_PREMODERATED_CATEGORY_IDS, $legacyPremodIDs);
        }
    }

    /**
     * @inheritdoc
     */
    public function premoderateItem(PremoderationItem $item): PremoderationResponse
    {
        if ($item->recordType === "user") {
            // We don't validate registrations/users.
            return PremoderationResponse::valid();
        }

        $systemUserID = $this->userModel->getSystemUserID();

        if ($this->requiresGlobalApproval($item)) {
            return new PremoderationResponse(
                PremoderationResponse::APPROVAL_REQUIRED,
                $systemUserID,
                PremoderationResponse::PREMODERATION_ROLE
            );
        }

        if ($this->requiresCategoryApproval($item)) {
            return new PremoderationResponse(
                PremoderationResponse::APPROVAL_REQUIRED,
                $systemUserID,
                PremoderationResponse::PREMODERATION_CATEGORY
            );
        }

        // Verified users and admins should bypass keyword pre-moderation
        if ($this->session->isUserVerified() || $this->session->checkPermission("Garden.Moderation.Manage")) {
            return PremoderationResponse::valid();
        }

        $blockedKeyword = $this->tryGetBlockedKeyword($item);

        if ($blockedKeyword !== null) {
            $response = new PremoderationResponse(
                PremoderationResponse::APPROVAL_REQUIRED,
                $systemUserID,
                PremoderationResponse::PREMODERATION_KEYWORD
            );
            // We have a matching keyword.
            $note = <<<TWIG
<p>The following keyword requires approval:</p><ul><li>{{ keyword }}</li></ul>
TWIG;
            $noteHtml = $this->renderTwigFromString($note, ["keyword" => $blockedKeyword]);

            $response->setNoteHtml($noteHtml);
            return $response;
        }

        return PremoderationResponse::valid();
    }

    /**
     * Check if the item matches a premoderated keyword.
     *
     * @param PremoderationItem $item
     *
     * @return string|null
     */
    private function tryGetBlockedKeyword(PremoderationItem $item): ?string
    {
        $keywords = $this->getBlockedKeywords();
        $stringsToCheck = [
            $item->recordName,
            $this->formatService->renderPlainText($item->recordBody, $item->recordFormat),
        ];

        foreach ($stringsToCheck as $stringToCheck) {
            $match = KeywordUtils::checkMatch($stringToCheck, $keywords);
            if ($match !== false) {
                return $match;
            }
        }

        return null;
    }

    private function requiresCategoryApproval(PremoderationItem $item): bool
    {
        // Now do category specific premoderation.
        $discussionsRequireApproval = $this->config->get(self::CONF_PREMODERATED_DISCUSSIONS_ENABLED, false);
        $commentsRequireApproval = $this->config->get(self::CONF_PREMODERATED_COMMENTS_ENABLED, false);

        if ($item->recordType === "discussion" && !$discussionsRequireApproval) {
            return false;
        }

        if ($item->recordType === "comment" && !$commentsRequireApproval) {
            return false;
        }

        if ($item->placeRecordType !== "category") {
            return false;
        }

        return in_array($item->placeRecordID, $this->getPremoderatedCategoryIDs());
    }

    private function requiresGlobalApproval(PremoderationItem $item): bool
    {
        if ($this->session->isUserVerified()) {
            return false;
        }
        $permissions = $this->session->getPermissions();
        // This is a deprecated permission, but it hasn't been removed yet.
        return $permissions->has("Vanilla.Approval.Require");
    }

    /**
     * @return int[]
     */
    private function getPremoderatedCategoryIDs(): array
    {
        $categoryIDsRequiringApproval = $this->config->get(self::CONF_PREMODERATED_CATEGORY_IDS, []);
        if (is_string($categoryIDsRequiringApproval)) {
            $categoryIDsRequiringApproval = explode(",", $categoryIDsRequiringApproval);
        }

        $ids = array_map(function (mixed $id) {
            return is_int($id) ? $id : (int) trim($id);
        }, $categoryIDsRequiringApproval);
        return $ids;
    }

    /**
     * @return string[]
     */
    private function getBlockedKeywords(): array
    {
        $words = [];

        $wordsString = $this->config->get(self::CONF_PREMODERATED_KEYWORDS, "");
        if (!empty($wordsString)) {
            $explodedWords = explode(";", $wordsString);
            foreach ($explodedWords as $index => $word) {
                $word = trim($word);

                if (strlen($word)) {
                    $explodedWords[$index] = $word;
                } else {
                    unset($explodedWords[$index]);
                }
            }

            $words = $explodedWords;
        }
        return $words;
    }
}
