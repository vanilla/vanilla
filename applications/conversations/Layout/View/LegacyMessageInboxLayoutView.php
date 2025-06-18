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
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Inbox Page";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "inbox";
    }

    /**
     * @inheritdoc
     */
    public function getLegacyType(): string
    {
        return "Conversations/messages/inbox";
    }
}
