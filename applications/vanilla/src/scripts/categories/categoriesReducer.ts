/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { produce } from "immer";
import CategorySuggestionActions from "@vanilla/addon-vanilla/categories/CategorySuggestionActions";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import clone from "lodash/clone";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";

export interface ICategoriesState {
    suggestionsByQuery: Record<string, ILoadable<ICategory[]>>;
}

const INITIAL_STATE: ICategoriesState = {
    suggestionsByQuery: {},
};

export const categoriesReducer = produce(
    reducerWithInitialState(clone(INITIAL_STATE))
        .case(CategorySuggestionActions.loadCategories.started, (nextState, payload) => {
            const { query } = payload;
            nextState.suggestionsByQuery[query] = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(CategorySuggestionActions.loadCategories.done, (nextState, payload) => {
            const { query } = payload.params;
            nextState.suggestionsByQuery[query].status = LoadStatus.SUCCESS;
            nextState.suggestionsByQuery[query].data = payload.result;
            return nextState;
        })
        .case(CategorySuggestionActions.loadCategories.failed, (nextState, payload) => {
            const { query } = payload.params;
            nextState.suggestionsByQuery[query].status = LoadStatus.ERROR;
            nextState.suggestionsByQuery[query].error = payload.error;
            return nextState;
        }),
);
