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
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Widget } from "@library/layout/Widget";
import { MetaLink } from "@library/metas/Metas";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDiscussionThreadPageContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { ThreadItem } from "@vanilla/addon-vanilla/thread/ThreadItem";
import React, { useEffect } from "react";
import { useDispatch } from "react-redux";

interface IProps {
    discussion: IDiscussion;
    category: ICategoryFragment;
}

export function DiscussionOriginalPostAsset(props: IProps) {
    const { discussion, category } = props;
    const { page } = useDiscussionThreadPageContext();

    // Some redus stuff wants the discussion in there.
    const dispatch = useDispatch();
    useEffect(() => {
        dispatch(
            DiscussionActions.getDiscussionByIDACs.done({
                params: { discussionID: discussion.discussionID },
                result: discussion,
            }),
        );
    }, [discussion]);

    const currentUserSignedIn = useCurrentUserSignedIn();
    useFallbackBackUrl(category.url);

    const toShare: ShareData = {
        url: discussion.url,
        title: discussion.name,
    };

    return (
        <Widget>
            <PageHeadingBox
                title={discussion.name}
                includeBackLink
                actions={
                    currentUserSignedIn && (
                        <div className={css({ display: "flex", alignItems: "center", gap: 4 })}>
                            <DiscussionBookmarkToggle discussion={discussion} />
                            <DiscussionOptionsMenu discussion={discussion} />
                        </div>
                    )
                }
            />
            <PageBox
                options={{
                    borderType: BorderType.SEPARATOR,
                }}
            >
                <ThreadItem
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
            </PageBox>
        </Widget>
    );
}
export default DiscussionOriginalPostAsset;
