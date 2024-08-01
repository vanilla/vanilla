<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Conversations\Layout;

/**
 * Legacy view type for inbox.
 */
class LegacyMessageInboxLayoutView implements \Vanilla\Layout\View\LegacyLayoutViewInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Inbox Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "inbox";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Conversations/messages/inbox";
    }
}
