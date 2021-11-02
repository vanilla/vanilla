/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useEffect, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";

import { useToggleDiscussionBookmarked } from "@library/features/discussions/discussionHooks";
import BookmarkToggle from "@library/bookmarkToggle/BookmarkToggle";

const DiscussionBookmarkToggle: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const { toggleDiscussionBookmarked, isBookmarked } = useToggleDiscussionBookmarked(discussion.discussionID);

    async function handleToggleBookmarked() {
        toggleDiscussionBookmarked(!isBookmarked);
    }

    return <BookmarkToggle bookmarked={!!isBookmarked} onToggleBookmarked={handleToggleBookmarked} />;
};

export default DiscussionBookmarkToggle;
