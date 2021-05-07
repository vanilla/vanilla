/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { ITag } from "@library/features/tags/TagsReducer";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ICategoryFragment } from "@vanilla/addon-vanilla/@types/api/categories";

export interface IDiscussion {
    discussionID: number;
    type: string;
    name: string;
    url: string;
    canonicalUrl: string;
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
    resolved?: boolean;
    countViews: number;
    countComments: number;
    attributes?: any;

    // expands
    lastUser?: IUserFragment; // expand;
    insertUser?: IUserFragment; // expand;
    breadcrumbs?: ICrumb[];
    categoryID: number;
    category?: ICategoryFragment;
    excerpt?: string;
    body?: string;
    tags?: ITag[];

    pinLocation?: "recent" | "category";

    // Per-session
    unread?: boolean;
    countUnread?: number;
    bookmarked?: boolean;

    reactions?: IReaction[];
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

export interface IGetDiscussionListParams {
    expand: string | string[];
}
