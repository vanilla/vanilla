/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable } from "@library/@types/api/core";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { RecordID } from "@vanilla/utils";

export interface ICategoryFragment {
    categoryID: number;
    name: string;
    url: string;
    allowedDiscussionTypes?: string[];
}

export interface ICategory extends ICategoryFragment {
    description: RecordID;
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
    followed: boolean;
    depth: number;
    breadcrumbs?: ICrumb[];
    children: ICategory[];
    dateInserted: string;
    iconUrl?: string;
}

export enum CategoryDisplayAs {
    CATEGORIES = "categories",
    DEFAULT = "default",
    DISCUSSIONS = "discussions",
    FLAT = "flat",
    HEADING = "heading",
}

export enum CategoryPostNotificationType {
    FOLLOW = "follow",
    DISCUSSIONS = "discussions",
    ALL = "all",
}
export interface ICategoryPreferences {
    "preferences.followed": boolean;
    "preferences.email.comments": boolean;
    "preferences.email.posts": boolean;
    "preferences.popup.comments": boolean;
    "preferences.popup.posts": boolean;
    "preferences.email.digest"?: boolean;
}

export const DEFAULT_NOTIFICATION_PREFERENCES: ICategoryPreferences = {
    "preferences.followed": false,
    "preferences.email.comments": false,
    "preferences.email.posts": false,
    "preferences.popup.comments": false,
    "preferences.popup.posts": false,
};

export const CATEGORIES_STORE_KEY = "categories";
