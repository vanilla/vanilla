/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { IImage } from "@library/@types/api/core";
import { ITag } from "@library/features/tags/TagsReducer";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { RecordID } from "@vanilla/utils";

export interface IDiscussion {
    discussionID: RecordID;
    type: string;
    name: string;
    url: string;
    canonicalUrl: string;
    dateInserted: string;
    insertUserID: number;
    lastUserID?: number;
    dateUpdated?: string;
    dateLastComment?: string;
    image?: IImage;

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
    discussionID: IDiscussion["discussionID"];
    body: string;
    format: string;
}

export interface IDiscussionEmbed {
    discussionID: IDiscussion["discussionID"];
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
    limit?: number;
    page?: number;
    discussionID?: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>;
    expand?: string | string[];
    followed?: boolean;
    featuredImage?: boolean;
    fallbackImage?: string;
}
