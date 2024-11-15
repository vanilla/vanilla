/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { STORY_DISCUSSION } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionOriginalPost",
};

interface IProps extends Partial<React.ComponentProps<typeof DiscussionOriginalPostAsset>> {}

const StoryDiscussionOriginalPostAsset = (props?: IProps) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
                staleTime: Infinity,
            },
        },
    });

    return (
        <QueryClientProvider client={queryClient}>
            <DiscussionOriginalPostAsset
                category={STORY_DISCUSSION.category}
                discussion={STORY_DISCUSSION}
                {...props}
            />
        </QueryClientProvider>
    );
};

export const Default = () => {
    return <StoryDiscussionOriginalPostAsset />;
};

export const CurrentUser = () => {
    return (
        <CurrentUserContextProvider currentUser={STORY_DISCUSSION.insertUser}>
            <PermissionsFixtures.AllPermissions>
                <StoryDiscussionOriginalPostAsset />
            </PermissionsFixtures.AllPermissions>
        </CurrentUserContextProvider>
    );
};
