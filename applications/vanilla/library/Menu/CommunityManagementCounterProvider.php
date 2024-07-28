<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Menu;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Menu\Counter;
use Vanilla\Menu\CounterProviderInterface;

/**
 * Counters for community management tasks.
 */
class CommunityManagementCounterProvider implements CounterProviderInterface
{
    /**
     * DI.
     */
    public function __construct(
        private \Gdn_Session $session,
        private \DiscussionStatusModel $discussionStatusModel,
        private ReportModel $reportModel,
        private EscalationModel $escalationModel,
        private \LogModel $logModel,
        private ConfigurationInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMenuCounters(): array
    {
        $counters = [];
        $permissions = $this->session->getPermissions();

        // Legacy log model counts.
        if (!FeatureFlagHelper::featureEnabled("escalations")) {
            if ($permissions->hasAny(["community.moderate"])) {
                $recordCount = $this->logModel->getOperationCount("spam");
                $counters[] = new Counter("SpamQueue", $recordCount);
                $recordCount = $this->logModel->getOperationCount("moderate,pending");
                $counters[] = new Counter("ModerationQueue", $recordCount);
            }
        } else {
            // New community management
            if ($permissions->hasAny(["community.moderate", "posts.moderate"])) {
                $counters[] = new Counter(
                    "escalations",
                    $this->escalationModel->queryEscalationsCount([
                        "status" => [EscalationModel::STATUS_OPEN, EscalationModel::STATUS_IN_PROGRESS],
                    ])
                );

                $counters[] = new Counter(
                    "reports",
                    $this->reportModel->countVisibleReports([
                        "status" => ReportModel::STATUS_NEW,
                    ])
                );
            }
        }

        if ($this->config->get("triage.enabled") && $permissions->has("staff.allow")) {
            $counters[] = new Counter(
                "triage",
                $this->discussionStatusModel->getCountStatusID(
                    [RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED],
                    isInternal: true
                )
            );
        }
        return $counters;
    }
}
