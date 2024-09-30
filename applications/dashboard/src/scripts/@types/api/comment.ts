/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion, IReportMeta } from "@dashboard/@types/api/discussion";
import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";

export interface IPremoderatedRecordResponse {
    status: 202;
    message: string;
    escalationID?: number;
}

export interface IComment {
    name: string;
    commentID: number;
    discussionID: IDiscussion["discussionID"];
    categoryID?: ICategory["categoryID"];
    body: string;
    dateInserted: string;
    dateUpdated: string | null;
    insertUserID: number;
    score: number | null;
    insertUser: IUserFragment;
    url: string;
    attributes: any;
    reactions?: IReaction[];
    attachments?: IAttachment[];
    reportMeta?: IReportMeta;
    suggestion?: ISuggestedAnswer;
    experimentalTrending?: number;
    experimentalTrendingDebug?: {
        plainText: {
            template: string;
            equation: string;
        };
        mathMl: {
            template: string;
            equation: string;
        };
    };
}

export interface ICommentEdit {
    commentID: number;
    discussionID: IDiscussion["discussionID"];
    body: string;
    format: string;
}

export interface ICommentEmbed {
    commentID: number;
    type: "quote";
    dateInserted: string;
    dateUpdated: string | null;
    insertUser: IUserFragment;
    url: string;
    format: string;
    body?: string;
    bodyRaw: string;
}

export enum QnAStatus {
    ACCEPTED = "accepted",
    REJECTED = "rejected",
    PENDING = "pending",
}

export enum CommentListSortOption {
    OLDEST = "dateInserted",
    NEWEST = "-dateInserted",
    TOP = "-score",
    TRENDING = "-experimentalTrending",
}
