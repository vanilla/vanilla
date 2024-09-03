import { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { cx } from "@emotion/css";
import { CommentThreadProvider, useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { ThreadItemHole } from "@vanilla/addon-vanilla/thread/ThreadItemHole";
import { ThreadItemComment } from "@vanilla/addon-vanilla/thread/ThreadItemComment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";

interface IProps extends IThreadResponse {
    discussion: IDiscussion;
}

export function NestedCommentsList(props: IProps) {
    return (
        <CommentThreadProvider {...props}>
            <PartialCommentsList discussion={props.discussion} />
        </CommentThreadProvider>
    );
}

export function PartialCommentsList(props: Partial<IProps>) {
    const { threadStructure, discussion } = useCommentThread();
    const thread = props.threadStructure ?? threadStructure;
    const parentRecord = props.discussion ?? discussion;
    const classes = nestCommentListClasses();
    return (
        <>
            {thread.map((commentOrHole: IThreadItem) => {
                const id = commentOrHole.type === "comment" ? commentOrHole.commentID : commentOrHole.holeID;
                const key = `${commentOrHole.parentCommentID}${id}`;
                return (
                    <span className={cx(classes.item, commentOrHole.type)} key={key}>
                        {commentOrHole.type === "comment" && (
                            <ThreadItemComment threadItem={commentOrHole} discussion={parentRecord} />
                        )}
                        {commentOrHole.type === "hole" && <ThreadItemHole threadItem={commentOrHole} />}
                    </span>
                );
            })}
        </>
    );
}
