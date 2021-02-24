/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ICategoryFragment } from "@vanilla/addon-vanilla/@types/api/categories";

export interface IDiscussion {
    discussionID: number;
    type: string;
    name: string;
    url: string;
    dateInserted: string;
    insertUserID: number;
    lastUserID?: number;
    dateUpdated?: string;
    dateLastComment?: string;

    // Stats
    pinned: boolean;
    closed: boolean;
    score: number;
    sink?: boolean;
    countViews: number;
    countComments: number;
    attributes?: any;

    // expands
    lastUser?: IUserFragment; // expand;
    insertUser?: IUserFragment; // expand;
    breadcrumbs?: ICrumb[];
    category?: ICategoryFragment;
    excerpt?: string;
    body?: string;

    // Per-session
    unread?: boolean;
    countUnread?: number;
    bookmarked?: boolean;
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
