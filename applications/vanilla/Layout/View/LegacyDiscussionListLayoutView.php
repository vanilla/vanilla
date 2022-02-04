<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Legacy view type for discussion list
 */
class LegacyDiscussionListLayoutView implements LegacyLayoutViewInterface {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return "Discussions Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return "discussionList";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string {
        return "Vanilla/Discussions/Index";
    }
}
