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
    customPermissions: false;
    isArchived: false;
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
}

export enum CategoryDisplayAs {
    CATEGORIES = "categories",
    DEFAULT = "default",
    DISCUSSIONS = "discussions",
    FLAT = "flat",
    HEADING = "heading",
}

export type CategoryPostNotificationType = "follow" | "discussions" | "all" | null;

export interface ICategoryPreferences {
    useEmailNotifications: boolean;
    postNotifications: CategoryPostNotificationType;
}

export const DEFAULT_NOTIFICATION_PREFERENCES: ICategoryPreferences = {
    useEmailNotifications: false,
    postNotifications: null,
};

export const CATEGORIES_STORE_KEY = "categories";
