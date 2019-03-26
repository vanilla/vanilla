<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Menu;

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for discussion model.
 */
class DiscussionCounterProvider implements CounterProviderInterface {

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(\DiscussionModel $discussionModel) {
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters[] = new Counter("Participated", $this->discussionModel->getCountParticipated());
        return $counters;
    }
}
