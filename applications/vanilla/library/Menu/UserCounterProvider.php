<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Menu;

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for user.
 */
class UserCounterProvider implements CounterProviderInterface {

    /** @var \Gdn_Session */
    private $session;

    /**
     * Initialize class with dependencies
     *
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters = [];
        if (is_object($this->session->User)) {
            $user = $this->session->User;
            $counters[] = new Counter("Bookmarks", $user->CountBookmarks ?? 0);
            $counters[] = new Counter("Discussions", $user->CountDiscussions ?? 0);
            $counters[] = new Counter("UnreadDiscussions", $user->CountUnreadDiscussions ?? 0);
            $counters[] = new Counter("Drafts", $user->CountDrafts ?? 0);
        }
        return $counters;
    }
}
