/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { ISearchScopeNoCompact } from "@library/features/search/SearchScopeContext";
import { type ISearchResult } from "@library/search/searchTypes";
import { formatUrl } from "@library/utility/appUtils";
import { convertSearchAPIParamsToURL } from "@library/widget-fragments/SearchFragment.utils";
import { useQuery, UseQueryResult } from "@tanstack/react-query";
import React from "react";

const makeSearchPageUrl = (params: SearchFragmentInjectable.SearchQuery): string => {
    const searchParams = convertSearchAPIParamsToURL(params);
    return formatUrl(`/search?${searchParams}`, true);
};

const useSearchQuery = (
    props: SearchFragmentInjectable.SearchQuery,
): UseQueryResult<SearchFragmentInjectable.SearchResult[] | undefined, Error> => {
    const {
        query,
        recordTypes,
        categoryID,
        includeChildCategories,
        knowledgeBaseID,
        knowledgeCategoryIDs,
        locale,
        page,
        limit,
        types,
        ...rest
    } = props;

    const searchQuery = useQuery<any, Error, SearchFragmentInjectable.SearchResult[] | undefined>({
        queryKey: [
            "search",
            query,
            recordTypes,
            categoryID,
            includeChildCategories,
            knowledgeBaseID,
            knowledgeCategoryIDs,
            locale,
            page,
            limit,
            types,
            rest,
        ],
        queryFn: async () => {
            const response = await apiv2.get<SearchFragmentInjectable.SearchResult[]>("/search", {
                params: {
                    query,
                    recordTypes,
                    categoryID,
                    includeChildCategories,
                    knowledgeBaseID,
                    knowledgeCategoryIDs,
                    locale,
                    page,
                    limit,
                    types,
                    ...rest,
                },
            });
            return response.data;
        },
        enabled: false,
    });

    return searchQuery;
};

namespace SearchFragmentInjectable {
    export interface Props {
        title?: string;
        description?: string;
        subtitle?: string;
        scope?: ISearchScopeNoCompact;
        placeholder?: string;
        initialParams?: React.ComponentProps<typeof IndependentSearch>["initialParams"];
        hideButton?: boolean;
        domain?: string;
        postType?: string;
        borderRadius?: string;
    }

    export interface SearchResult extends ISearchResult {}

    type SearchExpand =
        | "all"
        | "-body"
        | "insertUser"
        | "breadcrumbs"
        | "excerpt"
        | "image"
        | "insertUser.ssoID"
        | "insertUser.profileFields"
        | "insertUser.extended";

    export interface SearchQuery {
        query?: string;
        recordTypes?: string[];
        categoryID?: string;
        includeChildCategories?: boolean;
        knowledgeBaseID?: string;
        knowledgeCategoryIDs?: string[];
        locale?: string;
        page?: number;
        limit?: number;
        expands?: SearchExpand[];
        types?: string[];
        [key: string]: any;
    }
}

const SearchFragmentInjectable = {
    useSearchQuery,
    makeSearchPageUrl,
};

export default SearchFragmentInjectable;
