import { IUserFragment } from "@dashboard/@types/api";

export interface IComment {
    commentID: number;
    discussionID: number;
    body: string;
    dateInserted: string;
    dateUpdated: string | null;
    insertUserID: number;
    score: null;
    insertUser: IUserFragment;
    url: string;
    attributes: any;
}

export interface ICommentEdit {
    commentID: number;
    discussionID: number;
    body: string;
    format: string;
}

export interface ICommentEmbed {
    commentID: number;
    body: string;
    dateInserted: string;
    dateUpdated: string | null;
    insertUser: IUserFragment;
    url: string;
    format: string;
    bodyRaw: string;
}
