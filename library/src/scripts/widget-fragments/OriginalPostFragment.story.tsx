/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import OriginalPostFragment from "@library/widget-fragments/OriginalPostFragment.template";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { STORY_DISCUSSION, STORY_ME_ADMIN } from "@library/storybook/storyData";
import { ContentItemContextProvider } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import "./OriginalPostFragment.template.css";
import { setMeta } from "@library/utility/appUtils";

export default {
    title: "Fragments/OriginalPost",
};

const Template = (props) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
                staleTime: Infinity,
            },
        },
    });

    setMeta("triage.enabled", true);

    return (
        <QueryClientProvider client={queryClient}>
            <ContentItemContextProvider
                recordID={STORY_DISCUSSION.discussionID}
                recordType="discussion"
                recordUrl={STORY_DISCUSSION.url}
                timestamp={STORY_DISCUSSION.dateInserted}
                name={STORY_DISCUSSION.name}
                authorID={STORY_DISCUSSION.insertUserID}
                insertUser={STORY_DISCUSSION.insertUser}
                updateUser={STORY_DISCUSSION.updateUser}
                dateUpdated={STORY_DISCUSSION.dateUpdated}
            >
                <OriginalPostFragment
                    category={STORY_DISCUSSION.category!}
                    discussion={{ ...STORY_DISCUSSION, pinned: true, closed: true }}
                    onReply={() => {}}
                />
            </ContentItemContextProvider>
        </QueryClientProvider>
    );
};

export const Default = () => {
    return <Template />;
};

export const CurrentUser = () => {
    return (
        <CurrentUserContextProvider currentUser={{ ...STORY_ME_ADMIN, ...STORY_DISCUSSION.insertUser }}>
            <PermissionsFixtures.AllPermissions>
                <Template />
            </PermissionsFixtures.AllPermissions>
        </CurrentUserContextProvider>
    );
};
