/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { categoriesReducer } from "@vanilla/addon-vanilla/categories/categoriesReducer";
import { combineReducers } from "redux";
import { IForumState } from "./state";

export const forumReducer = combineReducers<IForumState>({
    categories: categoriesReducer,
});
