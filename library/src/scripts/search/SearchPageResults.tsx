/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { SearchPageResultsLoader } from "@library/search/SearchPageResultsLoader";
import { SearchPagination } from "@library/search/SearchPagination";
import { ISearchResult } from "@library/search/searchTypes";
import { SearchService } from "@library/search/SearchService";
import { useSearchForm } from "@library/search/SearchContext";
import { useLastValue } from "@vanilla/react-utils";
import { hashString } from "@vanilla/utils";
import React, { useLayoutEffect } from "react";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { IUser } from "@library/@types/api/users";
import { ALL_CONTENT_DOMAIN_NAME } from "@library/search/searchConstants";
import { makeSearchUrl } from "@library/search/SearchPageRoute";
import { formatUrl, t } from "@library/utility/appUtils";
import qs from "qs";
import { sprintf } from "sprintf-js";
import SmartLink from "@library/routing/links/SmartLink";
import { metasClasses } from "@library/metas/Metas.styles";

interface IProps {}

export function SearchPageResults(props: IProps) {
    const { updateForm, results, form, getCurrentDomain } = useSearchForm<{}>();
    const currentDomain = getCurrentDomain();

    const status = results.status;
    const lastStatus = useLastValue(status);
    useLayoutEffect(() => {
        if (lastStatus === LoadStatus.SUCCESS && status === LoadStatus.LOADING) {
            window.scrollTo({ top: 0 });
        }
    }, [status, lastStatus]);

    switch (results.status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            return <SearchPageResultsLoader count={3} />;
        case LoadStatus.ERROR:
            return <CoreErrorMessages error={results.error} />;
        case LoadStatus.SUCCESS:
            const { next, prev } = results.data!.pagination;
            let paginationNextClick: React.MouseEventHandler | undefined;
            let paginationPreviousClick: React.MouseEventHandler | undefined;

            if (next) {
                paginationNextClick = (e) => {
                    updateForm({ page: next });
                };
            }
            if (prev) {
                paginationPreviousClick = (e) => {
                    updateForm({ page: prev });
                };
            }
            return (
                <>
                    <AnalyticsData uniqueKey={hashString(form.query + JSON.stringify(results.data!.pagination))} />
                    <ResultList
                        result={currentDomain.ResultComponent}
                        results={results.data!.results.map(mapResult)}
                        ResultWrapper={currentDomain.ResultWrapper}
                        rel={"noindex nofollow"}
                    />
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
function mapResult(searchResult: ISearchResult): IResult | undefined {
    const crumbs = searchResult.breadcrumbs || [];
    const icon = SearchService.getSubType(searchResult.type)?.icon;

    return {
        name: searchResult.name,
        excerpt: searchResult.body,
        icon,
        meta: <MetaFactory searchResult={searchResult} />,
        image: searchResult.image?.url,
        url: searchResult.url,
        location: crumbs,
        userInfo: searchResult.userInfo,
        highlight: searchResult.highlight,
    };
}

function MetaFactory(props: { searchResult: ISearchResult }) {
    const { searchResult } = props;
    const { getDomains, getCurrentDomain, form, updateForm } = useSearchForm();
    const currentDomain = getCurrentDomain();

    const crumbs =
        currentDomain.hasSpecificRecord?.(form) && !currentDomain.showSpecificRecordCrumbs?.()
            ? []
            : searchResult.breadcrumbs;

    const foundDomain = getDomains().find((domain) => {
        return domain.getRecordTypes().includes(searchResult.recordType) && domain.key !== ALL_CONTENT_DOMAIN_NAME;
    });
    const CustomMetaComponent = foundDomain?.MetaComponent;

    let extraResults: React.ReactNode = null;
    if (searchResult.subqueryExtraParams && searchResult.subqueryMatchCount && searchResult.subqueryMatchCount > 1) {
        // We have "extra" results that can be drilled into.
        // Mix the subquery with the existing form.
        const query = {
            ...form,
            ...searchResult.subqueryExtraParams,
        };
        let root = searchResult.siteDomain ?? "";
        if (root && window.location.href.startsWith(root)) {
            root = formatUrl("", true);
        }
        const searchPath = makeSearchUrl();
        const queryString = qs.stringify(query);

        const url = `${root}${searchPath}?${queryString}`;
        const text = sprintf(t("%s results"), searchResult.subqueryMatchCount);

        const classesMeta = metasClasses();
        extraResults = (
            <SmartLink to={url} className={classesMeta.metaLink} style={{ fontWeight: "bold" }}>
                {text}
            </SmartLink>
        );
    }

    if (CustomMetaComponent) {
        return <CustomMetaComponent searchResult={searchResult} />;
    } else {
        return (
            <ResultMeta
                status={searchResult.status}
                type={searchResult.recordType}
                updateUser={searchResult.insertUser!}
                dateUpdated={searchResult.dateInserted}
                labels={searchResult.labelCodes}
                crumbs={crumbs}
                isForeign={searchResult.isForeign}
                extra={extraResults}
            />
        );
    }
}
