import { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { cx } from "@emotion/css";
import { CommentThreadProvider, useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { ThreadItemHole } from "@vanilla/addon-vanilla/thread/ThreadItemHole";
import { ThreadItemComment } from "@vanilla/addon-vanilla/thread/ThreadItemComment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useRef, useEffect } from "react";

interface IProps extends IThreadResponse {
    discussion: IDiscussion;
    rootClassName?: string;
}

export function NestedCommentsList(props: IProps) {
    return (
        <CommentThreadProvider {...props}>
            <PartialCommentsList discussion={props.discussion} />
        </CommentThreadProvider>
    );
}

export function PartialCommentsList(props: Partial<IProps>) {
    const { threadStructure, discussion, addLastChildRefID } = useCommentThread();
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
            {thread.map((commentOrHole: IThreadItem, index) => {
                const id = commentOrHole.type === "comment" ? commentOrHole.commentID : commentOrHole.holeID;
                const key = `${commentOrHole.parentCommentID}${id}`;
                const isLast = index === thread.length - 1;
                const refProps = {
                    ...(isLast && { ref: lastChildRef }),
                };

                return (
                    <div
                        className={cx(props.rootClassName, commentOrHole.type)}
                        data-depth={commentOrHole.depth}
                        data-id={commentOrHole.parentCommentID}
                        key={key}
                        {...refProps}
                    >
                        {commentOrHole.type === "comment" && (
                            <ThreadItemComment threadItem={commentOrHole} discussion={parentRecord} />
                        )}
                        {commentOrHole.type === "hole" && <ThreadItemHole threadItem={commentOrHole} />}
                    </div>
                );
            })}
        </>
    );
}
