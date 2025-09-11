/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { RecordID } from "@vanilla/utils";
import { IFollowedCategoryNotificationPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostType } from "@dashboard/postTypes/postType.types";
import type { IUserFragment } from "@library/@types/api/users";
import type { ImageSourceSet } from "@library/utility/appUtils";

export interface ICategoryFragment {
    categoryID: number;
    name: string;
    url: string;
    allowedDiscussionTypes?: string[];
}

export interface ILastPost {
    discussionID: number;
    commentID?: number;
    name: string;
    url: string;
    dateInserted: string;
    insertUserID: number;
    insertUser: IUserFragment;
}

export interface ICategory extends ICategoryFragment {
    description: string;
    parentCategoryID: number | null;
    customPermissions: boolean;
    isArchived: boolean;
    urlcode: string;
    displayAs: CategoryDisplayAs;
    countCategories: number;
    countDiscussions: number;
    countComments: number;
    countAllDiscussions: number;
    countAllComments: number;
    countFollowers: number;
    followed: boolean;
    depth: number;
    breadcrumbs?: ICrumb[];
    children: ICategory[];
    dateInserted: string;
    iconUrl?: string;
    iconUrlSrcSet?: ImageSourceSet;
    bannerImageUrl?: string;
    bannerImageUrlSrcSet?: ImageSourceSet;
    dateFollowed?: string;
    preferences?: IFollowedCategoryNotificationPreferences;
    lastPost?: ILastPost;
    allowedDiscussionTypes?: string[];
    allowedPostTypeIDs?: Array<PostType["postTypeID"]>;
    allowedPostTypeOptions?: PostType[];
    hasRestrictedPostTypes?: boolean;
}

export const CategoryDisplayAs = {
    Categories: "categories",
    Default: "default",
    Discussions: "discussions",
    Flat: "flat",
    Heading: "heading",
} as const;
export type CategoryDisplayAs = (typeof CategoryDisplayAs)[keyof typeof CategoryDisplayAs];

export enum CategoryPostNotificationType {
    FOLLOW = "follow",
    DISCUSSIONS = "discussions",
    ALL = "all",
}
