<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Models\ContentDraftModel;
use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinkProviderInterface;

/**
 * Provide quick links related to the current user.
 */
class ForumQuickLinksProvider implements QuickLinkProviderInterface
{
    /**
     * DI.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(private \Gdn_Session $session, private ContentDraftModel $contentDraftModel)
    {
    }

    /**
     * @inheritdoc
     */
    public function provideQuickLinks(): array
    {
        $result = [];
        $result[] = new QuickLink("All Categories", "/categories", null, -4, "discussions.view");

        $result[] = new QuickLink("Recent Posts", "/discussions", null, -3, "discussions.view");

        $result[] = new QuickLink("Activity", "/activity", null, -2, "discussions.view");

        $result[] = new QuickLink(
            "My Bookmarks",
            "/discussions/bookmarked",
            $this->session->User->CountBookmarks ?? 0,
            -1,
            "session.valid"
        );

        $result[] = new QuickLink(
            "My Posts",
            "/discussions/mine",
            $this->session->User->CountDiscussions ?? 0,
            -1,
            "session.valid"
        );
        $result[] = new QuickLink(
            "My Drafts",
            "/drafts",
            $this->contentDraftModel->draftsWhereCountByUser(),
            -1,
            "session.valid"
        );

        return $result;
    }
}
