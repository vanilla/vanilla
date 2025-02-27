/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IUserFragment } from "@library/@types/api/users";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";

export interface IThreadItemComment {
    /* Represents a comment in the nested structure */
    type: "comment";
    /* ID of the direct parent comment */
    parentCommentID: IComment["commentID"] | null;
    /* Level of nesting */
    depth: number;
    /* ID of the comment */
    commentID: IComment["commentID"];
    /* Replies to this comment */
    children?: IThreadItem[];
    /* Dot delimited location of comment relative to root */
    path: string;
}

export interface IThreadItemHole {
    /* Represents a gap in the nested structure */
    type: "hole";
    /* ID of the direct parent comment */
    parentCommentID: IComment["commentID"] | null;
    /* Level of nesting */
    depth: number;
    /* A generated hole ID based on the parent comment and offset */
    holeID?: string;
    /* Position from which new comments should be fetched*/
    offset: number;
    /* The first 5 users who are in the missing comments */
    insertUsers: IUserFragment[];
    /* Total number of comments in the hole */
    countAllComments: number;
    /* Total number of user who have made a commented in the hole */
    countAllInsertUsers: number;
    /* URL to get additional comments*/
    apiUrl: string;
    /* Dot delimited location of hole relative to root */
    path: string;
}

export interface IThreadItemReply {
    /* Represents a reply to a parent comment nested structure */
    type: "reply";
    /* ID of the direct parent comment */
    parentCommentID: IComment["commentID"] | null;
    /* Level of nesting */
    depth: number;
    /* A generated ID based on the parent comment and number of editors */
    replyID?: string;
    /* The first 5 users who are in the missing comments */
    draftID?: IDraft["draftID"];
    /* Dot delimited location of editor relative to root */
    path: string;
    /** User name of which this comment will reply to */
    replyingTo: string;
}

export type IThreadItem = IThreadItemComment | IThreadItemHole | IThreadItemReply;

export interface IThreadResponse {
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
}

export type CommentDraftParentIDAndPath = {
    parentCommentID: number | null;
    path: string;
} | null;
