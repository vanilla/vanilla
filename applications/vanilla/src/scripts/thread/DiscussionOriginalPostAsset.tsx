/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import DiscussionActions from "@library/features/discussions/DiscussionActions";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { MetaLink } from "@library/metas/Metas";
import { Tag } from "@library/metas/Tags";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDiscussionThreadPageContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { ThreadItem } from "@vanilla/addon-vanilla/thread/ThreadItem";
import { t } from "@vanilla/i18n";
import React, { useEffect } from "react";
import { useDispatch } from "react-redux";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread//DiscussionThread.hooks";

interface IProps {
    discussion: IDiscussion;
    category: ICategoryFragment;
}

export function DiscussionOriginalPostAsset(props: IProps) {
    const { discussion: discussionPreload, category } = props;
    const { discussionID } = discussionPreload;

    const { page } = useDiscussionThreadPageContext();

    // Some redux stuff wants the discussion in there.
    const dispatch = useDispatch();
    useEffect(() => {
        dispatch(
            DiscussionActions.getDiscussionByIDACs.done({
                params: { discussionID: discussionID },
                result: discussionPreload,
            }),
        );
    }, [discussionPreload]);

    const {
        query: { data },
        invalidate: invalidateDiscussionQuery,
    } = useDiscussionQuery(discussionID, discussionPreload);

    const currentUserSignedIn = useCurrentUserSignedIn();
    useFallbackBackUrl(category.url);

    const discussion = data!;

    const toShare: ShareData = {
        url: discussion!.url,
        title: discussion!.name,
    };

    return (
        <>
            <PageHeadingBox
                title={
                    <>
                        <span>{discussion.name}</span>
                        {discussion.closed && (
                            <Tag
                                className={discussionThreadClasses().closedTag}
                                preset={discussionListVariables().labels.tagPreset}
                            >
                                {t("Closed")}
                            </Tag>
                        )}
                    </>
                }
                includeBackLink
                actions={
                    currentUserSignedIn && (
                        <div className={css({ display: "flex", alignItems: "center", gap: 4 })}>
                            <DiscussionBookmarkToggle discussion={discussion} onSuccess={invalidateDiscussionQuery} />
                            <DiscussionOptionsMenu
                                discussion={discussion}
                                onMutateSuccess={invalidateDiscussionQuery}
                            />
                        </div>
                    )
                }
            />

            <ThreadItem
                boxOptions={{
                    borderType: BorderType.NONE,
                }}
                user={discussion.insertUser!}
                content={discussion.body!}
                contentMeta={
                    <MetaLink to={discussion.url}>
                        <DateTime timestamp={discussion.dateInserted}></DateTime>
                    </MetaLink>
                }
                userPhotoLocation={"header"}
                collapsed={page > 1}
                recordID={discussion.discussionID}
                recordType={"discussion"}
            />
        </>
    );
}
export default DiscussionOriginalPostAsset;
