/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISearchForm, ISearchRequestQuery, ISearchResponse } from "@library/search/searchTypes";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";

const createAction = actionCreatorFactory("@@search");

export class SearchActions {
    public static performSearchACs = createAction.async<ISearchRequestQuery, ISearchResponse, IApiError>("SEARCH");
    public static updateSearchFormAC = createAction<Partial<ISearchForm>>("UPDATE_FORM");
    public static resetFormAC = createAction("RESET_FORM");

    public static performDomainSearchACs = createAction.async<ISearchRequestQuery, ISearchResponse, IApiError>(
        "DOMAIN_SEARCH",
    );
}
