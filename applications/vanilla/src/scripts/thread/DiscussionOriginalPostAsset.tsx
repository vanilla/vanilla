/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Tag } from "@library/metas/Tags";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDiscussionThreadPaginationContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadPaginationContext";
import { ThreadItem } from "@vanilla/addon-vanilla/thread/ThreadItem";
import { t } from "@vanilla/i18n";
import React from "react";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { DiscussionThreadContextProvider } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { ThreadItemContextProvider } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import { Icon } from "@vanilla/icons";
import { ReportCountMeta } from "@vanilla/addon-vanilla/thread/ReportCountMeta";
import { getMeta } from "@library/utility/appUtils";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { PageBox } from "@library/layout/PageBox";
import { DiscussionsApi } from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    category: ICategoryFragment;
    containerOptions?: IHomeWidgetContainerOptions;
    title?: string;
    titleType: "discussion/name" | "none";
}

const discussionOriginalPostAssetClasses = () => {
    const container = css({
        position: "relative",
        container: "originalPostContainer / inline-size",
    });
    const actions = css({
        display: "flex",
        alignItems: "center",
        gap: 4,

        "@container originalPostContainer (width: 500px)": {
            gap: 16,
        },
    });
    return {
        container,
        actions,
    };
};

export function DiscussionOriginalPostAsset(props: IProps) {
    const { discussion: discussionPreload, discussionApiParams, category } = props;
    const { discussionID } = discussionPreload;

    const classes = discussionOriginalPostAssetClasses();

    const { hasPermission } = usePermissionsContext();
    const { page } = useDiscussionThreadPaginationContext();

    const {
        query: { data },
        invalidate: invalidateDiscussionQuery,
    } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);

    const currentUserSignedIn = useCurrentUserSignedIn();
    useFallbackBackUrl(category.url);

    const discussion = data!;

    const showResolved = hasPermission("staff.allow") && getMeta("triage.enabled", false);

    const postMeta = (
        <>
            {showResolved && (
                <span className={discussionThreadClasses().resolved}>
                    <Icon icon={discussion.resolved ? "cmd-approve" : "cmd-alert"} />
                </span>
            )}
            {discussion.closed && (
                <Tag
                    className={discussionThreadClasses().closedTag}
                    preset={discussionListVariables().labels.tagPreset}
                >
                    {t("Closed")}
                </Tag>
            )}
        </>
    );

    const actions = currentUserSignedIn && (
        <div className={classes.actions}>
            {props.titleType === "none" ? postMeta : null}
            <ReportCountMeta
                countReports={discussion.reportMeta?.countReports}
                recordID={discussion.discussionID}
                recordType="discussion"
            />
            <DiscussionBookmarkToggle discussion={discussion} onSuccess={invalidateDiscussionQuery} />
            <DiscussionOptionsMenu discussion={discussion} onMutateSuccess={invalidateDiscussionQuery} />
        </div>
    );

    return (
        <HomeWidgetContainer
            options={{ ...props.containerOptions }}
            depth={1}
            title={
                props.titleType !== "none" ? (
                    <>
                        <span>{props.title ?? discussion.name}</span>
                        {postMeta}
                    </>
                ) : undefined
            }
            actions={props.titleType !== "none" ? actions : undefined}
        >
            <DiscussionThreadContextProvider discussion={discussion}>
                <ThreadItemContextProvider
                    threadStyle={"flat"}
                    recordType={"discussion"}
                    recordID={discussion.discussionID}
                    recordUrl={discussion.url}
                    name={discussion.name}
                    timestamp={discussion.dateInserted}
                    attributes={discussion.attributes}
                    authorID={discussion.insertUserID}
                >
                    <ThreadItem
                        boxOptions={{
                            borderType: BorderType.NONE,
                        }}
                        user={discussion.insertUser!}
                        content={discussion.body!}
                        userPhotoLocation={"header"}
                        collapsed={page > 1}
                        reactions={discussion.type !== "idea" ? discussion.reactions : undefined}
                        categoryID={discussion.categoryID}
                        options={props.titleType === "none" ? actions : undefined}
                    />
                </ThreadItemContextProvider>
            </DiscussionThreadContextProvider>
        </HomeWidgetContainer>
    );
}
export default DiscussionOriginalPostAsset;
