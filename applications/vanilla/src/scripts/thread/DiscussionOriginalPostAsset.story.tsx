/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { STORY_DISCUSSION } from "@library/storybook/storyData";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionOriginalPost",
};

export const Default = () => {
    return <DiscussionOriginalPostAsset category={STORY_DISCUSSION.category} discussion={STORY_DISCUSSION} />;
};

export const CurrentUser = () => {
    const discussion = STORY_DISCUSSION;
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            ...STORY_DISCUSSION.insertUser,
                            countUnreadNotifications: 0,
                            countUnreadConversations: 0,
                        },
                    },
                },
            }}
        >
            <DiscussionOriginalPostAsset category={discussion.category} discussion={discussion} />
        </TestReduxProvider>
    );
};
