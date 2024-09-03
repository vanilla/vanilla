/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";
import { PartialCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";

interface IProps {
    threadItem: IThreadItem & { type: "comment" };
    discussion: IDiscussion;
}

export function ThreadItemComment(props: IProps) {
    const { getComment } = useCommentThread();
    const comment = getComment(props.threadItem.commentID);
    const classes = nestCommentListClasses();
    return (
        <>
            {comment && (
                <span data-depth={props.threadItem.depth} className={cx("commentItem")}>
                    <CommentThreadItem comment={comment} discussion={props.discussion} />
                </span>
            )}
            {props.threadItem.children && props.threadItem.children.length > 0 && (
                <span className={cx(classes.children, "commentChildren")}>
                    <PartialCommentsList threadStructure={props.threadItem.children} />
                </span>
            )}
        </>
    );
}
