<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for user conversations.
 */
class ConversationCounterProvider implements CounterProviderInterface {

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
            $counters[] = new Counter("Conversations", $user->CountUnreadConversations ?? 0);
        }
        return $counters;
    }
}
