/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { produce } from "immer";
import CategorySuggestionActions from "@vanilla/addon-vanilla/categories/CategorySuggestionActions";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import clone from "lodash/clone";
import { ICategory } from "@vanilla/addon-vanilla/@types/api/categories";

export interface ICategoriesState {
    suggestions: ILoadable<ICategory[]>;
}

const INITIAL_STATE: ICategoriesState = {
    suggestions: {
        status: LoadStatus.PENDING,
    },
};

export const categoriesReducer = produce(
    reducerWithInitialState(clone(INITIAL_STATE))
        .case(CategorySuggestionActions.loadCategories.started, (nextState, payload) => {
            nextState.suggestions.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(CategorySuggestionActions.loadCategories.done, (nextState, payload) => {
            nextState.suggestions.status = LoadStatus.SUCCESS;
            nextState.suggestions.data = payload.result;
            return nextState;
        })
        .case(CategorySuggestionActions.loadCategories.failed, (nextState, payload) => {
            nextState.suggestions.status = LoadStatus.ERROR;
            nextState.suggestions.error = payload.error;
            return nextState;
        }),
);
