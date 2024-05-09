/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IApiError, LoadStatus } from "@library/@types/api/core";
import debounce from "lodash/debounce";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { IForumStoreState } from "@vanilla/addon-vanilla/redux/state";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

const createAction = actionCreatorFactory("@@categorySuggestions");

export default class CategorySuggestionActions extends ReduxActions<IForumStoreState & ICoreStoreState> {
    public static loadCategories = createAction.async<{ query: string }, ICategory[], IApiError>("GET");

    private internalLoadCategories = (query: string, parentCategoryID?: number | null) => {
        const { suggestionsByQuery } = this.getState().forum.categories;
        const existingLoadable = suggestionsByQuery[query] ?? { status: LoadStatus.PENDING };
        if (existingLoadable.status === LoadStatus.LOADING || existingLoadable.status === LoadStatus.SUCCESS) {
            // Already loading.
            return;
        }
        const apiThunk = bindThunkAction(CategorySuggestionActions.loadCategories, async () => {
            // See if we have an existing item.
            const params = { query, parentCategoryID, expand: ["breadcrumbs"] };
            const response = await this.api.get("/categories/search", { params });
            return response.data;
        })({ query });
        return this.dispatch(apiThunk);
    };

    public loadCategories = debounce(this.internalLoadCategories, 100);
}
