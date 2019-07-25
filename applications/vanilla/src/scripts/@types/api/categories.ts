/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface ICategory {
    categoryID: number;
    name: string;
    description: string;
    parentCategoryID: number | null;
    customPermissions: false;
    isArchived: false;
    urlcode: string;
    url: string;
    displayAs: CategoryDisplayAs;
    countCategories: number;
    countDiscussions: number;
    countComments: number;
    countAllDiscussions: number;
    countAllComments: number;
    followed: boolean;
    depth: number;
    children: ICategory[];
}

enum CategoryDisplayAs {
    CATEGORIES = "categories",
    DEFAULT = "default",
    DISCUSSIONS = "discussions",
    FLAT = "flat",
    HEADING = "heading",
}
