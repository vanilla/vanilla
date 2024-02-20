/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { SearchPageResultsLoader } from "@library/search/SearchPageResultsLoader";
import { SearchPagination } from "@library/search/SearchPagination";
import { ISearchResult } from "@library/search/searchTypes";
import { useSearchForm } from "@library/search/SearchFormContext";
import { useLastValue } from "@vanilla/react-utils";
import React, { useLayoutEffect, useMemo } from "react";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { makeSearchUrl } from "@library/search/SearchPageRoute";
import { formatUrl, t } from "@library/utility/appUtils";
import qs from "qs";
import { sprintf } from "sprintf-js";
import { MetaLink } from "@library/metas/Metas";
import QueryString from "qs";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ALL_CONTENT_DOMAIN_KEY } from "./searchConstants";

export function SearchPageResults() {
    const { updateForm, response, currentDomain, domains, form } = useSearchForm<{}>();

    const getDomainForResultType = (resultType: string) => {
        const specificDomains = domains.filter(({ key }) => key !== ALL_CONTENT_DOMAIN_KEY);
        return (
            specificDomains.find(({ recordTypes }) => recordTypes.includes(resultType)) ??
            specificDomains.find(({ subTypes }) => subTypes.map((subType) => subType.type).includes(resultType))!
        );
    };

    /**
     * Map a result from the search API response into what the <ResultList /> component is expecting.
     *
     * @param searchResult The API search result to map.
     */
    function mapResult(searchResult: ISearchResult): IResult {
        const resultDomain = getDomainForResultType(searchResult.type ?? searchResult.recordType) ?? currentDomain;
        const crumbs = resultDomain.getSpecificRecordID?.(form)
            ? currentDomain.showSpecificRecordCrumbs
                ? searchResult.breadcrumbs
                : []
            : searchResult.breadcrumbs;

        const mappedResult = resultDomain.mapResultToProps(searchResult);

        return {
            ...mappedResult,
            meta: (
                <MetaFactory MetaComponent={resultDomain.MetaComponent} crumbs={crumbs} searchResult={searchResult} />
            ),
        };
    }

    const results = useMemo<IResult[]>(() => {
        if (response?.data?.results) {
            return response.data.results.map((result) => mapResult(result));
        }
        return [];
    }, [response]);

    const status = response.status;
    const lastStatus = useLastValue(status);
    useLayoutEffect(() => {
        if (lastStatus === LoadStatus.SUCCESS && status === LoadStatus.LOADING) {
            window.scrollTo({ top: 0 });
        }
    }, [status, lastStatus]);

    let content = <></>;
    switch (response.status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            content = <SearchPageResultsLoader count={3} />;
            break;
        case LoadStatus.ERROR:
            content = <CoreErrorMessages error={response.error} />;
            const { message } = response.error ?? { message: "" };
            if (message === "canceled") {
                content = <SearchPageResultsLoader count={3} />;
            }
            break;

        case LoadStatus.SUCCESS:
            const { next, prev, nextURL, prevURL } = response.data!.pagination;
            let paginationNextClick: React.MouseEventHandler | undefined;
            let paginationPreviousClick: React.MouseEventHandler | undefined;

            if (next) {
                paginationNextClick = (e) => {
                    updateForm({ page: next });
                };
            } else {
                if (nextURL) {
                    paginationNextClick = (e) => {
                        const params = QueryString.parse(nextURL.split("?")?.[1] ?? "");
                        updateForm({
                            pageURL: nextURL,
                            ...(params["offset"] && { offset: parseInt(`${params.offset}`) }),
                        });
                    };
                }
            }
            if (prev) {
                paginationPreviousClick = (e) => {
                    updateForm({ page: prev });
                };
            } else {
                if (prevURL) {
                    paginationPreviousClick = (e) => {
                        const params = QueryString.parse(prevURL.split("?")?.[1] ?? "");
                        updateForm({
                            pageURL: prevURL,
                            ...(params["offset"] && { offset: parseInt(`${params.offset}`) }),
                        });
                    };
                }
            }

            content = (
                <>
                    <ResultList
                        results={results}
                        ResultComponent={currentDomain.ResultComponent}
                        ResultWrapper={currentDomain.ResultWrapper}
                        rel={"noindex nofollow"}
                    />
                    {results.length > 0 && (
                        <SearchPagination onNextClick={paginationNextClick} onPreviousClick={paginationPreviousClick} />
                    )}
                </>
            );
            break;
    }

    return content;
}

export function MetaFactory(props: {
    searchResult: ISearchResult;
    MetaComponent?: React.ComponentType<any>;
    crumbs?: ICrumb[];
}) {
    const { searchResult, MetaComponent, crumbs } = props;
    const {
        subqueryExtraParams,
        subqueryMatchCount,
        siteDomain,
        status,
        recordType,
        insertUser,
        dateUpdated,
        dateInserted,
        labelCodes,
        isForeign,
    } = searchResult;

    const { form } = useSearchForm();

    let extraResults: React.ReactNode = null;
    if (subqueryExtraParams && subqueryMatchCount && subqueryMatchCount > 1) {
        // We have "extra" results that can be drilled into.
        // Mix the subquery with the existing form.
        const query = {
            ...form,
            ...subqueryExtraParams,
            page: 1, // start from the first page in the extra results
        };
        let root = siteDomain ?? "";
        if (root && window.location.href.startsWith(root)) {
            root = formatUrl("", true);
        }
        const searchPath = makeSearchUrl();
        const queryString = qs.stringify(query);

        const url = `${root}${searchPath}?${queryString}`;
        const text = sprintf(t("%s results"), subqueryMatchCount);

        extraResults = (
            <MetaLink to={url} style={{ fontWeight: "bold" }}>
                {text}
            </MetaLink>
        );
    }

    if (MetaComponent) {
        return <MetaComponent searchResult={searchResult} />;
    } else {
        return (
            <ResultMeta
                status={status}
                type={recordType}
                updateUser={insertUser}
                dateUpdated={dateUpdated ?? dateInserted}
                labels={labelCodes}
                crumbs={crumbs}
                isForeign={isForeign}
                extra={extraResults}
            />
        );
    }
}
