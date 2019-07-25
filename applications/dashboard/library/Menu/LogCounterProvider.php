<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for log model.
 */
class LogCounterProvider implements CounterProviderInterface {

    /** @var \LogModel */
    private $logModel;

    /** @var \Gdn_Session */
    private $session;

    /**
     * Initialize class with dependencies
     *
     * @param \LogModel $logModel
     * @param \Gdn_Session $session
     */
    public function __construct(
        \LogModel $logModel,
        \Gdn_Session $session
    ) {
        $this->logModel = $logModel;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters = [];
        $permissions = $this->session->getPermissions();
        if ($permissions->hasAny(['Garden.Moderation.Manage', 'Garden.Spam.Manage'])) {
            $recordCount = $this->logModel->getCountWhere(['Operation' => ['Spam']]);
            $counters[] = new Counter("SpamQueue", $recordCount);
        }

        if ($permissions->hasAny(['Garden.Moderation.Manage', 'Moderation.ModerationQueue.Manage'])) {
            $recordCount = $this->logModel->getCountWhere(['Operation' => ['Moderate', 'Pending']]);
            $counters[] = new Counter("ModerationQueue", $recordCount);
        }
        return $counters;
    }
}
