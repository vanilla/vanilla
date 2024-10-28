/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { RecordID } from "@vanilla/utils";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { IDiscussion } from "@dashboard/@types/api/discussion";

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
    dateFollowed?: string;
    preferences?: ICategoryPreferences;
    lastPost?: IDiscussion;
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
