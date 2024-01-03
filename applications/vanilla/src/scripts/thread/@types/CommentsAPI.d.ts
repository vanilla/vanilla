/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare namespace CommentsApi {
    export interface IndexParams {
        discussionID: RecordID;
        limit: number;
        page: number;
    }

    export interface PostParams {
        body: string;
        format: string;
        discussionID: RecordID;
        draftID?: RecordID;
    }

    export interface PatchParams {
        body: string;
        format: string;
        commentID: RecordID;
    }

    export type CommentListData = IWithPaging<IComment[]>;
}
