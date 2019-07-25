/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ICategoriesState } from "@vanilla/addon-vanilla/categories/categoriesReducer";

export interface IForumState {
    categories: ICategoriesState;
}

export interface IForumStoreState {
    forum: IForumState;
}
