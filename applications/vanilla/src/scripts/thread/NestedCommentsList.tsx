import { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { cx } from "@emotion/css";
import { CommentThreadProvider, useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { ThreadItemHole } from "@vanilla/addon-vanilla/thread/ThreadItemHole";
import { ThreadItemComment } from "@vanilla/addon-vanilla/thread/ThreadItemComment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useRef, useEffect } from "react";
import { getThreadItemID } from "@vanilla/addon-vanilla/thread/threadUtils";
import { ThreadItemReply } from "@vanilla/addon-vanilla/thread/ThreadItemReply";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IComment } from "@dashboard/@types/api/comment";
import { IDraftProps } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { getMeta } from "@library/utility/appUtils";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";

interface IProps extends IThreadResponse {
    discussion: IDiscussion;
    rootClassName?: string;
    discussionApiParams?: DiscussionsApi.GetParams;
    comments?: IWithPaging<IComment[]>;
    apiParams?: CommentsApi.IndexParams;
    renderTitle?: boolean;
    draft?: IDraftProps;
    showOPTag?: boolean;
    isPreview?: boolean;
}

export function NestedCommentsList(props: IProps) {
    const threadDepthLimit = getMeta("threadDepth", 5);

    return (
        <CommentThreadProvider threadDepthLimit={threadDepthLimit} {...props}>
            <PartialCommentsList discussion={props.discussion} isPreview={props.isPreview} />
        </CommentThreadProvider>
    );
}

export function PartialCommentsList(props: Partial<IProps>) {
    const { threadStructure, discussion, addLastChildRefID, showOPTag } = useCommentThread();
    const thread = props.threadStructure ?? threadStructure;
    const parentRecord = props.discussion ?? discussion;

    const lastChildRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (lastChildRef && lastChildRef.current) {
            const id = lastChildRef.current.getAttribute("data-id");
            if (id) {
                addLastChildRefID(id, lastChildRef);
            }
        }
    }, [thread, lastChildRef]);

    return (
        <>
            {thread.map((threadItem: IThreadItem, index) => {
                const id = getThreadItemID(threadItem);
                const key = `${threadItem.parentCommentID}${id}`;
                const isLast = index === thread.length - 1;
                const refProps = {
                    ...(isLast && { ref: lastChildRef }),
                };

                return (
                    <PageBox
                        options={{ borderType: threadItem.depth <= 1 ? BorderType.SEPARATOR : BorderType.NONE }}
                        className={cx(props.rootClassName, threadItem.type)}
                        data-depth={threadItem.depth}
                        data-id={threadItem.parentCommentID}
                        key={key}
                        {...refProps}
                    >
                        {threadItem.type === "comment" && (
                            <ThreadItemComment
                                threadItem={threadItem}
                                discussion={parentRecord}
                                showOPTag={showOPTag}
                                isPreview={props.isPreview}
                            />
                        )}
                        {threadItem.type === "hole" && <ThreadItemHole threadItem={threadItem} />}
                        {threadItem.type === "reply" && <ThreadItemReply threadItem={threadItem} />}
                    </PageBox>
                );
            })}
        </>
    );
}
