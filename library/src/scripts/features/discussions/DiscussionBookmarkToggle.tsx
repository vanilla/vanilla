/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useEffect, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";

import { useToggleDiscussionBookmarked } from "@library/features/discussions/discussionHooks";
import BookmarkToggle from "@library/bookmarkToggle/BookmarkToggle";

const DiscussionBookmarkToggle: FunctionComponent<{ discussion: IDiscussion; onSuccess?: () => Promise<void> }> = ({
    discussion,
    onSuccess,
}) => {
    const { toggleDiscussionBookmarked, isBookmarked } = useToggleDiscussionBookmarked(discussion.discussionID);

    async function handleToggleBookmarked() {
        await toggleDiscussionBookmarked(!isBookmarked);
        !!onSuccess && (await onSuccess());
    }

    return <BookmarkToggle bookmarked={!!isBookmarked} onToggleBookmarked={handleToggleBookmarked} />;
};

export default DiscussionBookmarkToggle;
