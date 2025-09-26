/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion, IReportMeta } from "@dashboard/@types/api/discussion";
import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment, IUserFragmentAndRoles } from "@library/@types/api/users";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { ICategory, type ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import type { IPostWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";
import type { VanillaSanitizedHtml } from "@vanilla/dom-utils";
import type { RecordID } from "@vanilla/utils";

export interface IPremoderatedRecordResponse {
    status: 202;
    message: string;
    escalationID?: number;
}

export interface IComment {
    name: string;
    commentID: number;
    parentRecordType: string;
    parentRecordID: RecordID;
    categoryID: ICategory["categoryID"];
    category?: ICategoryFragment;
    body: VanillaSanitizedHtml;
    dateInserted: string;
    dateUpdated: string | null;
    insertUserID: number;
    updateUserID?: number;
    updateUser?: IUserFragment | IUserFragmentAndRoles;
    score: number | null;
    insertUser: IUserFragment | IUserFragmentAndRoles;
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
    isTroll?: boolean;
    warning?: IPostWarning;
}

export interface ICommentEdit {
    commentID: number;
    parentRecordType: string;
    parentRecordID: RecordID;
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

export enum CommentThreadSortOption {
    OLDEST = "dateInserted",
    NEWEST = "-dateInserted",
    TOP = "-score",
    TRENDING = "-experimentalTrending",
}

export enum CommentDeleteMethod {
    FULL = "full",
    TOMBSTONE = "tombstone",
}
