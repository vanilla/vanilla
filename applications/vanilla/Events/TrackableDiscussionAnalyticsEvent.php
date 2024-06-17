<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

/**
 * Event for handling discussion tracking analytics.
 */
class TrackableDiscussionAnalyticsEvent
{
    protected array $discussion;

    /**
     * Constructor.
     *
     * @param array $discussion
     */
    public function __construct(array &$discussion)
    {
        $this->discussion = &$discussion;
    }

    /**
     * Get the discussion array.
     *
     * @return array
     */
    public function &getDiscussion(): array
    {
        return $this->discussion;
    }

    /**
     * Set the discussion array.
     *
     * @return array
     */
    public function setDiscussion(array $discussion): void
    {
        $this->discussion = $discussion;
    }
}
