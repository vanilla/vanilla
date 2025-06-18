/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { STORY_DISCUSSION, STORY_ME_ADMIN } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import OriginalPostAsset from "@vanilla/addon-vanilla/posts/OriginalPostAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionOriginalPost",
};

interface IProps extends Partial<React.ComponentProps<typeof OriginalPostAsset>> {}

const StoryOriginalPostAsset = (props?: IProps) => {
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
            <OriginalPostAsset
                category={STORY_DISCUSSION.category!}
                discussion={STORY_DISCUSSION}
                titleType={"discussion/name"}
                {...props}
            />
        </QueryClientProvider>
    );
};

export const Default = () => {
    return <StoryOriginalPostAsset />;
};

export const CurrentUser = () => {
    return (
        <CurrentUserContextProvider currentUser={{ ...STORY_ME_ADMIN, ...STORY_DISCUSSION.insertUser }}>
            <PermissionsFixtures.AllPermissions>
                <StoryOriginalPostAsset />
            </PermissionsFixtures.AllPermissions>
        </CurrentUserContextProvider>
    );
};
