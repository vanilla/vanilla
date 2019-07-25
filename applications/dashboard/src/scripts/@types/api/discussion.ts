/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";

export interface IDiscussion {
    discussionID: number;
    type: string | null;
    name: string;
    body: string;
    dateInserted: string;
    dateUpdated: string | null;
    insertUserID: number;
    score: number | null;
    insertUser: IUserFragment;
    lastUser: IUserFragment;
    pinned: boolean;
    closed: boolean;
    sink: boolean;
    bookmarked: boolean;
    unread: boolean;
    countUnread: number;
    url: string;
    countViews: number;
    countComments: number;
    attributes: any;
}

export interface IDiscussionEdit {
    commentID: number;
    discussionID: number;
    body: string;
    format: string;
}

export interface IDiscussionEmbed {
    discussionID: number;
    type: "quote";
    name: string;
    dateInserted: string;
    dateUpdated: string | null;
    insertUser: IUserFragment;
    url: string;
    format: string;
    body?: string;
    bodyRaw: string;
}
