/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";
import debounce from "lodash/debounce";
import { ICategory } from "@vanilla/addon-vanilla/@types/api/categories";

const createAction = actionCreatorFactory("@@categorySuggestions");

export default class CategorySuggestionActions extends ReduxActions {
    public static loadCategories = createAction.async<{ query: string }, ICategory[], IApiError>("GET");

    private interalLoadCategories = (query: string) => {
        const apiThunk = bindThunkAction(CategorySuggestionActions.loadCategories, async () => {
            if (query === "") {
                return [];
            }

            const params = { query };
            const response = await this.api.get("/categories/search", { params });
            return response.data;
        })({ query });
        return this.dispatch(apiThunk);
    };

    public loadCategories = debounce(this.interalLoadCategories, 100);
}
