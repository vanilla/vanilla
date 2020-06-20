/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUnifySearchResponseBody } from "@knowledge/@types/api/unifySearch";
import KbErrorMessages from "@knowledge/modules/common/KbErrorMessages";
import { SearchPagination } from "@library/search/SearchPagination";
import { LoadStatus } from "@library/@types/api/core";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import Loader from "@library/loaders/Loader";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { useSearchForm } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { hashString } from "@vanilla/utils";
import React from "react";
import { ISearchResult } from "@library/search/searchTypes";

interface IProps {}

export function SearchPageResults(props: IProps) {
    const { search, updateForm, results, form } = useSearchForm();

    switch (results.status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            return <Loader />;
        case LoadStatus.ERROR:
            return <KbErrorMessages apiError={results.error} />;
        case LoadStatus.SUCCESS:
            const { next, prev } = results.data!.pagination;
            let paginationNextClick: React.MouseEventHandler | undefined;
            let paginationPreviousClick: React.MouseEventHandler | undefined;

            if (next) {
                paginationNextClick = e => {
                    updateForm({ page: next });
                    void search();
                };
            }
            if (prev) {
                paginationPreviousClick = e => {
                    updateForm({ page: prev });
                    void search();
                };
            }
            return (
                <>
                    <AnalyticsData uniqueKey={hashString(form.query + JSON.stringify(results.data!.pagination))} />
                    <ResultList results={results.data!.results.map(mapResult)} />
                    <SearchPagination onNextClick={paginationNextClick} onPreviousClick={paginationPreviousClick} />
                </>
            );
    }
}

/**
 * Map a search API response into what the <SearchResults /> component is expecting.
 *
 * @param searchResult The API search result to map.
 */
function mapResult(searchResult: ISearchResult): IResult {
    const crumbs = searchResult.breadcrumbs || [];
    return {
        name: searchResult.name,
        excerpt: searchResult.body,
        meta: (
            <ResultMeta
                status={searchResult.status}
                type={searchResult.recordType}
                updateUser={searchResult.insertUser!}
                dateUpdated={searchResult.dateInserted}
                crumbs={crumbs}
            />
        ),
        image: searchResult.image?.url,
        url: searchResult.url,
        location: crumbs,
    };
}
