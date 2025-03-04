/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommentReply } from "@vanilla/addon-vanilla/comments/CommentReply";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/comments/NestedComments.classes";
import { useEffect, useRef } from "react";
import { ICommentEditorRefHandle } from "@vanilla/addon-vanilla/comments/CommentEditor";
import type { IThreadItem } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { useCreateCommentContext } from "@vanilla/addon-vanilla/posts/CreateCommentContext";

interface IProps {
    threadItem: IThreadItem & { type: "reply" };
}

/**
 * Renders a reply form to append to a parent comment
 */
export function CommentThreadReplyContainer(props: IProps) {
    const classes = nestCommentListClasses();
    const { setVisibleReplyFormRef } = useCreateCommentContext();

    const editorHandlerRef = useRef<ICommentEditorRefHandle>(null);

    useEffect(() => {
        if (editorHandlerRef.current?.formRef?.current) {
            setVisibleReplyFormRef && setVisibleReplyFormRef(editorHandlerRef.current.formRef);
        }
    }, [editorHandlerRef.current?.formRef]);

    return (
        <div className={classes.reply}>
            <CommentReply className={classes.replyEditor} threadItem={props.threadItem} ref={editorHandlerRef} />
        </div>
    );
}
