/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    CommentListSortOption,
    IComment,
    ICommentEdit,
    IPremoderatedRecordResponse,
} from "@dashboard/@types/api/comment";
import apiv2 from "@library/apiv2";
import SimplePagerModel, { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { RecordID } from "@vanilla/utils";

export const CommentsApi = {
    index: async (apiParams: CommentsApi.IndexParams): Promise<IWithPaging<IComment[]>> => {
        const result = await apiv2.get<IComment[]>("/comments", {
            params: apiParams,
        });

        const paging = SimplePagerModel.parseHeaders(result.headers);
        return {
            data: result.data,
            paging,
        };
    },
    threadIndex: async (apiParams: CommentsApi.IndexThreadParams): Promise<IWithPaging<IThreadResponse>> => {
        const result = await apiv2.get<IThreadResponse>("/comments/thread", {
            params: apiParams,
        });

        const paging = SimplePagerModel.parseHeaders(result.headers);
        return {
            data: result.data,
            paging,
        };
    },
    get: async (commentID: RecordID, params?: CommentsApi.SingleParams): Promise<IComment> => {
        const result = await apiv2.get<IComment>(`/comments/${commentID}`, {
            params,
        });
        return result.data;
    },
    getEdit: async (commentID: RecordID): Promise<ICommentEdit> => {
        const result = await apiv2.get<ICommentEdit>(`/comments/${commentID}/edit`);
        return result.data;
    },
    post: async (apiParams: CommentsApi.PostParams): Promise<IComment | IPremoderatedRecordResponse> => {
        const result = await apiv2.post("/comments", apiParams);
        return result.data;
    },
    patch: async (commentID: IComment["commentID"], apiParams: CommentsApi.PatchParams): Promise<IComment> => {
        const result = await apiv2.patch(`/comments/${commentID}`, apiParams);
        return result.data;
    },
    delete: async (commentID: RecordID) => {
        await apiv2.delete(`/comments/${commentID}`);
    },
};

export namespace CommentsApi {
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

    export interface GetCommentsQueryParams {
        expand?: string[];
    }
}
