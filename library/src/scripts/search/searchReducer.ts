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
import { SEARCH_SCOPE_LOCAL } from "@library/features/search/SearchScopeContext";

export interface ISearchState {
    form: ISearchForm;
    results: ILoadable<ISearchResults>;
    domainSearchResults: Record<string, ILoadable<ISearchResults>>;
}

export const DEFAULT_CORE_SEARCH_FORM: ISearchForm = {
    domain: ALL_CONTENT_DOMAIN_NAME,
    query: "",
    page: 1,
    sort: "relevance",
    scope: SEARCH_SCOPE_LOCAL,
    initialized: false,
};

export const INITIAL_SEARCH_STATE: ISearchState = {
    form: DEFAULT_CORE_SEARCH_FORM,
    results: {
        status: LoadStatus.PENDING,
    },
    domainSearchResults: {},
};

const reinitilizeParams = ["sort", "domain", "scope", "page"];

export const searchReducer = produce(
    reducerWithoutInitialState<ISearchState>()
        .case(SearchActions.updateSearchFormAC, (nextState, payload) => {
            nextState.form.needsResearch = false;
            if (nextState.form.initialized) {
                for (const reinitParam of reinitilizeParams) {
                    if (payload[reinitParam] !== undefined && nextState.form[reinitParam] !== payload[reinitParam]) {
                        nextState.form.needsResearch = true;
                    }
                }
            }

            if (!nextState.form.initialized && payload.initialized) {
                nextState.form.needsResearch = true;
            }

            nextState.form = {
                ...nextState.form,
                ...payload,
            };

            if (!payload.initialized) {
                // Only when the search form changes outside of initialization.
                if (!("page" in payload)) {
                    nextState.form.page = 1;
                }

                if ("domain" in payload) {
                    delete nextState.form.types;
                }
            }

            return nextState;
        })
        .case(SearchActions.performSearchACs.started, (nextState, payload) => {
            nextState.form.needsResearch = false;
            nextState.form.initialized = true;
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
        .case(SearchActions.performDomainSearchACs.started, (nextState, payload) => {
            const { domain } = payload;
            nextState.domainSearchResults[domain] = {
                status: LoadStatus.LOADING,
            };

            return nextState;
        })
        .case(SearchActions.performDomainSearchACs.done, (nextState, payload) => {
            const { domain } = payload.params;
            nextState.domainSearchResults[domain].status = LoadStatus.SUCCESS;
            nextState.domainSearchResults[domain].data = payload.result;

            return nextState;
        })
        .case(SearchActions.performDomainSearchACs.failed, (nextState, payload) => {
            const { domain } = payload.params;
            nextState.domainSearchResults[domain].status = LoadStatus.ERROR;
            nextState.domainSearchResults[domain].error = payload.error;

            return nextState;
        })
        .case(SearchActions.resetFormAC, (nextState) => {
            nextState.form = { ...DEFAULT_CORE_SEARCH_FORM };
            nextState.results = {
                status: LoadStatus.PENDING,
            };
            return nextState;
        }),
);
