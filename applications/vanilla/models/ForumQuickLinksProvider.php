<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinkProviderInterface;

/**
 * Provide quick links related to the current user.
 */
class ForumQuickLinksProvider implements QuickLinkProviderInterface
{
    /** @var \Gdn_Session */
    private $session;

    /**
     * DI.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function provideQuickLinks(): array
    {
        $result = [];
        $result[] = new QuickLink("All Categories", "/categories", null, -4, "discussions.view");

        $result[] = new QuickLink("Recent Discussions", "/discussions", null, -3, "discussions.view");

        $result[] = new QuickLink("Activity", "/activity", null, -2, "discussions.view");

        $result[] = new QuickLink(
            "My Bookmarks",
            "/discussions/bookmarked",
            $this->session->User->CountBookmarks ?? 0,
            -1,
            "session.valid"
        );

        $result[] = new QuickLink(
            "My Discussions",
            "/discussions/mine",
            $this->session->User->CountDiscussions ?? 0,
            -1,
            "session.valid"
        );

        $result[] = new QuickLink("My Drafts", "/drafts", $this->session->User->CountDrafts ?? 0, -1, "session.valid");

        return $result;
    }
}
