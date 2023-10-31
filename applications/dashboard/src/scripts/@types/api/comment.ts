/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IUserFragment } from "@library/@types/api/users";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";

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
