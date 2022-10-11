<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Legacy view type for discussion thread.
 */
class LegacyDiscussionThreadLayoutView implements LegacyLayoutViewInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Comments Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussionThread";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Discussion/Index";
    }
}
