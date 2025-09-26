<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Legacy view type for new discussion.
 */
class LegacyNewDiscussionLayoutView implements LegacyLayoutViewInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "New Discussion Form";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "newDiscussion";
    }

    /**
     * @inheritdoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Post/Discussion";
    }
}
