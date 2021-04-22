/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";

export interface ICategoryFragment {
    categoryID: number;
    name: string;
    url: string;
    allowedDiscussionTypes?: string[];
}

export interface ICategory extends ICategoryFragment {
    description: string;
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
}

enum CategoryDisplayAs {
    CATEGORIES = "categories",
    DEFAULT = "default",
    DISCUSSIONS = "discussions",
    FLAT = "flat",
    HEADING = "heading",
}
