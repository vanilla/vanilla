/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare namespace CommentsApi {
    export interface IndexParams {
        discussionID: string | number;
        limit: number;
        page: number;
        expand?: string[];
        qna?: string;
    }

    export interface PostParams {
        body: string;
        format: string;
        discussionID: string | number;
        draftID?: string | number;
    }

    export interface PatchParams {
        body: string;
        format: string;
    }

    export type CommentListData = IWithPaging<IComment[]>;
}
