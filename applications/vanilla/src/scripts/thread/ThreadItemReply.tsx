import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { ThreadCommentEditor } from "@vanilla/addon-vanilla/thread/components/ThreadCommentEditor";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";

interface IProps {
    threadItem: IThreadItem & { type: "reply" };
}

/**
 * Renders a reply form to append to a parent comment
 */
export function ThreadItemReply(props: IProps) {
    const classes = nestCommentListClasses();

    return (
        <div className={classes.reply}>
            <ThreadCommentEditor className={classes.replyEditor} threadItem={props.threadItem} />
        </div>
    );
}
