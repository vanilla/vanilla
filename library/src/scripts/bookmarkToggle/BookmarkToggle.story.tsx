/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import BookmarkToggleComponent from "@library/bookmarkToggle/BookmarkToggle";

export const BookmarkToggle = storyWithConfig(
    {
        themeVars: {},
    },
    () => {
        const [bookmarked, setBookmarked] = useState(false);

        async function handleToggleBookmarked(): Promise<void> {
            return new Promise((resolve, reject) => {
                setTimeout(() => {
                    setBookmarked(!bookmarked);
                    resolve();
                }, 100);
            });
        }

        return (
            <StoryContent>
                <BookmarkToggleComponent bookmarked={bookmarked} onToggleBookmarked={handleToggleBookmarked} />
            </StoryContent>
        );
    },
);

export default {
    title: "Components",
};
