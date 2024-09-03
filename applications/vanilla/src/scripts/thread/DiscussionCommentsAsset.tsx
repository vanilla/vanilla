/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Tag } from "@library/metas/Tags";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { t } from "@vanilla/i18n";
import React from "react";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { useCommentThreadQuery } from "@vanilla/addon-vanilla/thread/Comments.hooks";
import { getMeta } from "@library/utility/appUtils";
import FlatCommentList from "@vanilla/addon-vanilla/thread/FlatCommentList";
import { NestedCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";
import ButtonLoader from "@library/loaders/ButtonLoader";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    comments?: IWithPaging<IComment[]>;
    apiParams: CommentsApi.IndexParams;
    renderTitle?: boolean;
    ThreadItemActionsComponent?: React.ComponentType<{
        comment: IComment;
        discussion: IDiscussion;
        onMutateSuccess?: () => Promise<void>;
    }>;
}

export function DiscussionCommentsAsset(props: IProps) {
    const threadStyle = getMeta("threadStyle", "flat");
    return (
        <>
            {threadStyle === "flat" && <FlatCommentList {...props} />}
            {threadStyle === "nested" && <Thread {...props} />}
        </>
    );
}

function Thread(props: IProps) {
    const { data, isLoading } = useCommentThreadQuery({
        parentRecordID: props.discussion.discussionID,
        parentRecordType: "discussion",
        page: 1,
        limit: 30,
        expand: "all",
    });
    const { threadStructure, commentsByID } = data ?? {};

    return (
        <>
            {props.renderTitle && (
                <PageHeadingBox
                    title={
                        <div
                            className={css({
                                marginTop: 16,
                            })}
                        >
                            <span>{t("Comments")}</span>
                            {props.discussion.closed && (
                                <Tag
                                    className={discussionThreadClasses().closedTag}
                                    preset={discussionListVariables().labels.tagPreset}
                                >
                                    {t("Closed")}
                                </Tag>
                            )}
                        </div>
                    }
                    // TODO: Add pagination
                    // actions={hasPager && <NumberedPager {...pagerProps} rangeOnly />}
                />
            )}
            {isLoading && (
                <div style={{ display: "flex", width: "100%", padding: 16 }}>
                    <ButtonLoader className={css({ transform: "scale(2)" })} />
                </div>
            )}
            {!isLoading && threadStructure && commentsByID && (
                <NestedCommentsList
                    threadStructure={threadStructure}
                    commentsByID={commentsByID}
                    discussion={props.discussion}
                />
            )}
        </>
    );
}

export default DiscussionCommentsAsset;
