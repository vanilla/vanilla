/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare namespace CommentsApi {
    import { CommentListSortOption } from "@dashboard/@types/api/comment";

    export interface IndexParams {
        parentRecordType?: "discussion" | "escalation";
        parentRecordID?: string | number;
        discussionID?: string | number;
        limit: number;
        page: number;
        expand?: string[];
        qna?: string;
        insertUserRoleID?: number[];
        sentiment?: string[];
        sort?: CommentListSortOption;
    }

    export interface IndexThreadParams {
        parentRecordType: "discussion" | "escalation";
        parentRecordID: RecordID;
        page: number;
        limit: number;
        sort: CommentListSortOption;
        expand: string[];
    }

    export interface SingleParams {
        expand?: string[];
    }

    export interface PostParams {
        body: string;
        format: string;
        discussionID: string | number;
        draftID?: string | number;
        parentCommentID?: string | number;
    }

    export interface PatchParams {
        body: string;
        format: string;
    }

    export type CommentListData = IWithPaging<IComment[]>;
}
