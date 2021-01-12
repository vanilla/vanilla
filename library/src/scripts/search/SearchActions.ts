/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISearchForm, ISearchRequestQuery, ISearchResults } from "@library/search/searchTypes";
import { IApiError } from "@vanilla/library/src/scripts/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";

const createAction = actionCreatorFactory("@@search");

export class SearchActions {
    public static performSearchACs = createAction.async<ISearchRequestQuery, ISearchResults, IApiError>("SEARCH");
    public static updateSearchFormAC = createAction<Partial<ISearchForm>>("UPDATE_FORM");
    public static resetFormAC = createAction("RESET_FORM");

    public static performDomainSearchACs = createAction.async<ISearchRequestQuery, ISearchResults, IApiError>(
        "DOMAIN_SEARCH",
    );
}
