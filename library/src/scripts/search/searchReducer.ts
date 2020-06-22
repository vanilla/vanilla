/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { SearchActions } from "@library/search/SearchActions";
import { ALL_CONTENT_DOMAIN_NAME } from "@library/search/searchConstants";
import { ISearchForm, ISearchResults } from "@library/search/searchTypes";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { produce } from "immer";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";

export interface ISearchState {
    form: ISearchForm;
    results: ILoadable<ISearchResults>;
}

export const DEFAULT_CORE_SEARCH_FORM: ISearchForm = {
    domain: ALL_CONTENT_DOMAIN_NAME,
    query: "",
    page: 1,
};

export const INITIAL_SEARCH_STATE: ISearchState = {
    form: DEFAULT_CORE_SEARCH_FORM,
    results: {
        status: LoadStatus.PENDING,
    },
};

export const searchReducer = produce(
    reducerWithoutInitialState<ISearchState>()
        .case(SearchActions.updateSearchFormAC, (nextState, payload) => {
            nextState.form = {
                ...nextState.form,
                ...payload,
            };

            if (!("page" in payload)) {
                // Reset the page when switching some other filter.
                nextState.form.page = 1;
            }

            return nextState;
        })
        .case(SearchActions.performSearchACs.started, (nextState, payload) => {
            nextState.results.status = LoadStatus.LOADING;

            return nextState;
        })
        .case(SearchActions.performSearchACs.done, (nextState, payload) => {
            nextState.results.status = LoadStatus.SUCCESS;
            nextState.results.data = payload.result;

            return nextState;
        })
        .case(SearchActions.performSearchACs.failed, (nextState, payload) => {
            nextState.results.status = LoadStatus.ERROR;
            nextState.results.error = payload.error;

            return nextState;
        })
        .case(SearchActions.resetFormAC, nextState => {
            nextState.form = DEFAULT_CORE_SEARCH_FORM;
            return nextState;
        }),
);
