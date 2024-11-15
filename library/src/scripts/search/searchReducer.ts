/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { SearchActions } from "@library/search/SearchActions";
import { ISearchForm, ISearchResponse } from "@library/search/searchTypes";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { produce } from "immer";
import { reducerWithoutInitialState, type ReducerBuilder } from "typescript-fsa-reducers";
import { SEARCH_SCOPE_LOCAL } from "@library/features/search/SearchScopeContext";
import { EMPTY_SEARCH_DOMAIN_KEY } from "./searchConstants";

export interface ISearchState<ExtraFormValues extends object = {}> {
    form: ISearchForm<ExtraFormValues>;
    response: ILoadable<ISearchResponse>;
    domainSearchResponse: Record<string, ILoadable<ISearchResponse>>;
}

export const DEFAULT_CORE_SEARCH_FORM: ISearchForm = {
    domain: EMPTY_SEARCH_DOMAIN_KEY,
    query: "",
    page: 1,
    sort: "relevance",
    scope: SEARCH_SCOPE_LOCAL,
    initialized: false,
};

export const INITIAL_SEARCH_STATE: ISearchState = {
    form: DEFAULT_CORE_SEARCH_FORM,
    response: {
        status: LoadStatus.PENDING,
    },
    domainSearchResponse: {},
};

const reinitilizeParams = ["sort", "domain", "scope", "page", "pageURL"];

export const searchReducer = produce(
    reducerWithoutInitialState<ISearchState>()
        .case(SearchActions.updateSearchFormAC, (nextState, payload) => {
            let needsResearch = false;

            if (nextState.form.initialized) {
                for (const reinitParam of reinitilizeParams) {
                    if (payload[reinitParam] !== undefined && nextState.form[reinitParam] !== payload[reinitParam]) {
                        needsResearch = true;
                    }
                }
            }

            if (!nextState.form.initialized && payload.initialized) {
                needsResearch = true;
            }

            const nextForm = {
                ...nextState.form,
                ...payload,
                needsResearch,
            };

            if (!payload.initialized) {
                if (!("page" in payload)) {
                    nextForm.page = 1;
                }
            }

            nextState.form = nextForm;

            return nextState;
        })
        .case(SearchActions.performSearchACs.started, (nextState, payload) => {
            nextState.form.needsResearch = false;
            nextState.form.initialized = true;
            nextState.response.status = LoadStatus.LOADING;

            return nextState;
        })
        .case(SearchActions.performSearchACs.done, (nextState, payload) => {
            nextState.response.status = LoadStatus.SUCCESS;
            nextState.response.data = payload.result;

            return nextState;
        })
        .case(SearchActions.performSearchACs.failed, (nextState, payload) => {
            nextState.response.status = LoadStatus.ERROR;
            nextState.response.error = payload.error;

            return nextState;
        })
        .case(SearchActions.performDomainSearchACs.started, (nextState, payload) => {
            const { domain } = payload;
            nextState.domainSearchResponse[domain] = {
                status: LoadStatus.LOADING,
            };

            return nextState;
        })
        .case(SearchActions.performDomainSearchACs.done, (nextState, payload) => {
            const { domain } = payload.params;
            nextState.domainSearchResponse[domain].status = LoadStatus.SUCCESS;
            nextState.domainSearchResponse[domain].data = payload.result;

            return nextState;
        })
        .case(SearchActions.performDomainSearchACs.failed, (nextState, payload) => {
            const { domain } = payload.params;
            nextState.domainSearchResponse[domain].status = LoadStatus.ERROR;
            nextState.domainSearchResponse[domain].error = payload.error;

            return nextState;
        })
        .case(SearchActions.resetFormAC, (nextState) => {
            nextState.form = { ...DEFAULT_CORE_SEARCH_FORM };
            nextState.response = {
                status: LoadStatus.PENDING,
            };
            return nextState;
        }),
) as ReducerBuilder<ISearchState<{}>, ISearchState<{}>, ISearchState<{}>>;
