<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for activity model.
 */
class ActivityCounterProvider implements CounterProviderInterface {

    /** @var \ActivityModel */
    private $activityModel;

    /** @var \Gdn_Session */
    private $session;

    /**
     * Initialize class with dependencies
     *
     * @param \ActivityModel $activityModel
     * @param \Gdn_Session $session
     */
    public function __construct(
        \ActivityModel $activityModel,
        \Gdn_Session $session
    ) {
        $this->activityModel = $activityModel;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters[] = new Counter("UnreadNotifications", $this->activityModel->getUserTotalUnread($this->session->UserID));
        return $counters;
    }
}
