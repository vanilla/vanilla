/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import TabbedCommentListAssetPreview from "@QnA/asset/TabbedCommentListAsset.preview";

export default {
    title: "Widgets/Tabbed Comment List",
};

const StoryTabbedCommentList = () => {
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
            <TabbedCommentListAssetPreview
                threadStyle={"nested"}
                tabTitles={{
                    all: "Comments",
                    accepted: "Accepted Answers",
                    rejected: "Rejected Answers",
                }}
                apiParams={{ collapseChildDepth: 4, discussionID: 4, limit: 10, page: 1 }}
            />
        </QueryClientProvider>
    );
};

export function TabbedCommentList() {
    return (
        <PermissionsFixtures.AllPermissions>
            <StoryTabbedCommentList />
        </PermissionsFixtures.AllPermissions>
    );
}
