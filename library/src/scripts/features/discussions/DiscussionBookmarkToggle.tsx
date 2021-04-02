/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";

import { useToggleDiscussionBookmarked } from "@library/features/discussions/discussionHooks";
import BookmarkToggle from "@library/bookmarkToggle/BookmarkToggle";

const DiscussionBookmarkToggle: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const bookmarked = discussion.bookmarked ?? false;
    const toggleBookmarked = useToggleDiscussionBookmarked(discussion.discussionID);

    async function handleToggleBookmarked() {
        await toggleBookmarked(!bookmarked);
    }

    return <BookmarkToggle bookmarked={bookmarked} onToggleBookmarked={handleToggleBookmarked} />;
};

export default DiscussionBookmarkToggle;
