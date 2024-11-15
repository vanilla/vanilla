/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { ThreadCommentEditor } from "@vanilla/addon-vanilla/thread/components/ThreadCommentEditor";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";
import { useEffect, useRef } from "react";
import { ICommentEditorRefHandle } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";

interface IProps {
    threadItem: IThreadItem & { type: "reply" };
}

/**
 * Renders a reply form to append to a parent comment
 */
export function ThreadItemReply(props: IProps) {
    const classes = nestCommentListClasses();
    const { setVisibleReplyFormRef } = useCommentThread();

    const editorHandlerRef = useRef<ICommentEditorRefHandle>(null);

    useEffect(() => {
        if (editorHandlerRef.current?.formRef?.current) {
            setVisibleReplyFormRef && setVisibleReplyFormRef(editorHandlerRef.current.formRef);
        }
    }, []);

    return (
        <div className={classes.reply}>
            <ThreadCommentEditor className={classes.replyEditor} threadItem={props.threadItem} ref={editorHandlerRef} />
        </div>
    );
}
