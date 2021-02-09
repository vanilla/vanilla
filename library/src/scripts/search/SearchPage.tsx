/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import SearchBar from "@library/features/search/SearchBar";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import SearchOption from "@library/features/search/SearchOption";
import { ButtonTypes } from "@library/forms/buttonTypes";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import Drawer from "@library/layout/drawer/Drawer";
import { PageHeading } from "@library/layout/PageHeading";
import { pageTitleClasses } from "@library/layout/pageTitleStyles";
import DocumentTitle from "@library/routing/DocumentTitle";
import QueryString from "@library/routing/QueryString";
import { SearchInFilter } from "@library/search/SearchInFilter";
import { SearchPageResults } from "@library/search/SearchPageResults";
import { SortAndPaginationInfo } from "@library/search/SortAndPaginationInfo";
import { typographyClasses } from "@library/styles/typographyStyles";
// This new page must have our base reset in place.
import "@library/theming/reset";
import { t, formatUrl } from "@library/utility/appUtils";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { useSearchForm } from "@library/search/SearchContext";
import { useLastValue } from "@vanilla/react-utils";
import classNames from "classnames";
import debounce from "lodash/debounce";
import qs from "qs";
import React, { useCallback, useEffect } from "react";
import { useLocation, useHistory } from "react-router";
import TwoColumnLayout from "@library/layout/TwoColumnLayout";
import { LayoutProvider, useLayout } from "@library/layout/LayoutContext";
import PanelWidget from "@library/layout/components/PanelWidget";
import PanelWidgetHorizontalPadding from "@library/layout/components/PanelWidgetHorizontalPadding";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import {
    EmptySearchScopeProvider,
    SEARCH_SCOPE_LOCAL,
    useSearchScope,
} from "@library/features/search/SearchScopeContext";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import History from "history";
import { Backgrounds } from "@library/layout/Backgrounds";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";

interface IProps {
    placeholder?: string;
}

function SearchPage(props: IProps) {
    const {
        form,
        updateForm,
        search,
        results,
        getDomains,
        getCurrentDomain,
        getDefaultFormValues,
    } = useSearchForm<{}>();
    const { isCompact } = useLayout();
    const classes = pageTitleClasses();
    useInitialQueryParamSync();

    const currentDomain = getCurrentDomain();

    const debouncedSearch = useCallback(
        debounce(() => {
            search();
            currentDomain.extraSearchAction?.();
        }, 800),
        [search],
    );

    let scope = useSearchScope().value?.value ?? SEARCH_SCOPE_LOCAL;
    const lastScope = useLastValue(scope);
    if (currentDomain.isIsolatedType()) {
        scope = SEARCH_SCOPE_LOCAL;
    }

    let currentFilter = <currentDomain.PanelComponent />;

    const hasSpecificRecord = currentDomain.hasSpecificRecord?.(form);

    let SpecificRecordFilter;
    if (hasSpecificRecord && currentDomain.SpecificRecordPanel) {
        SpecificRecordFilter = currentDomain.SpecificRecordPanel;
    }

    let SpecificRecordComponent;
    if (hasSpecificRecord && currentDomain.SpecificRecordComponent) {
        SpecificRecordComponent = currentDomain.SpecificRecordComponent;
    }

    let hasSpecificRecordID = typeof currentDomain.getSpecificRecord?.(form) === "number";
    let specificRecordID;
    if (hasSpecificRecord && hasSpecificRecordID) {
        specificRecordID = currentDomain.getSpecificRecord?.(form);
    }

    const resultsHeader = (
        <PanelWidgetHorizontalPadding>
            <SortAndPaginationInfo
                pages={results.data?.pagination}
                sortValue={form.sort}
                onSortChange={(newSort) => updateForm({ sort: newSort })}
                sortOptions={currentDomain?.getSortValues() ?? []}
            />
        </PanelWidgetHorizontalPadding>
    );

    const { needsResearch } = form;
    useEffect(() => {
        // Trigger new search
        if (needsResearch || (lastScope && lastScope !== scope)) {
            search();
            currentDomain.extraSearchAction?.();
        }
    }, [search, needsResearch, lastScope, scope, currentDomain]);

    const domains = getDomains();
    const sortedNonIsolatedDomains = domains
        .filter((domain) => !domain.isIsolatedType())
        .sort((a, b) => a.sort - b.sort);

    return (
        // Add a context provider so that smartlinks within search use dynamic navigation.
        <LinkContextProvider linkContexts={[formatUrl("/search", true)]}>
            <DocumentTitle title={form.query ? form.query : t("Search Results")}>
                <TitleBar title={t("Search")} />
                <Banner isContentBanner />
                <Container>
                    <QueryString
                        value={{ ...form, initialized: undefined, scope, needsResearch: undefined }}
                        defaults={getDefaultFormValues()}
                    />
                    <TwoColumnLayout
                        className="hasLargePadding"
                        mainTop={
                            <>
                                <PanelWidget>
                                    <PageHeading
                                        className={classNames(
                                            "searchBar-heading",
                                            searchBarClasses({}).heading,
                                            classes.smallBackLink,
                                        )}
                                        headingClassName={classNames(typographyClasses().pageTitle)}
                                        title={"Search"}
                                        includeBackLink={true}
                                        isCompactHeading={true}
                                    />
                                    <ConditionalWrap
                                        condition={currentDomain.isIsolatedType()}
                                        component={EmptySearchScopeProvider}
                                    >
                                        <div className={searchBarClasses({}).standardContainer}>
                                            <SearchBar
                                                placeholder={props.placeholder}
                                                onChange={(newQuery) => updateForm({ query: newQuery })}
                                                value={form.query}
                                                onSearch={debouncedSearch}
                                                isLoading={results.status === LoadStatus.LOADING}
                                                optionComponent={SearchOption}
                                                triggerSearchOnClear={true}
                                                titleAsComponent={t("Search")}
                                                handleOnKeyDown={(event) => {
                                                    if (event.key === "Enter") {
                                                        debouncedSearch();
                                                    }
                                                }}
                                                disableAutocomplete={true}
                                                buttonBaseClass={ButtonTypes.PRIMARY}
                                                needsPageTitle={false}
                                                overwriteSearchBar={{
                                                    preset: SearchBarPresets.BORDER,
                                                }}
                                            />
                                        </div>
                                        {hasSpecificRecord && hasSpecificRecordID ? (
                                            <SpecificRecordComponent discussionID={specificRecordID} />
                                        ) : null}
                                    </ConditionalWrap>
                                    {!hasSpecificRecord && (
                                        <SearchInFilter
                                            setData={(newDomain) => {
                                                updateForm({ domain: newDomain });
                                            }}
                                            activeItem={form.domain}
                                            filters={sortedNonIsolatedDomains.map((domain) => {
                                                return {
                                                    label: domain.getName?.() || domain.name,
                                                    icon: domain.icon,
                                                    data: domain.key,
                                                };
                                            })}
                                            endFilters={domains
                                                .filter((domain) => domain.isIsolatedType())
                                                .map((domain) => {
                                                    return {
                                                        label: domain.name,
                                                        icon: domain.icon,
                                                        data: domain.key,
                                                    };
                                                })}
                                        />
                                    )}
                                    {PlacesSearchTypeFilter.searchTypes.length > 0 && currentDomain.heading}
                                </PanelWidget>
                                {isCompact && (
                                    <PanelWidgetHorizontalPadding>
                                        <Drawer title={t("Filter Results")}>{currentFilter}</Drawer>
                                    </PanelWidgetHorizontalPadding>
                                )}
                                {resultsHeader}
                            </>
                        }
                        mainBottom={<SearchPageResults />}
                        rightTop={
                            !isCompact && (
                                <PanelWidget>
                                    {hasSpecificRecord ? <SpecificRecordFilter /> : currentFilter}
                                </PanelWidget>
                            )
                        }
                    />
                </Container>
            </DocumentTitle>
        </LinkContextProvider>
    );
}

function useInitialQueryParamSync() {
    const { updateForm, resetForm, form, search } = useSearchForm<{}>();
    const history = useHistory();
    const location = useLocation();
    const searchScope = useSearchScope();

    const { initialized } = form;

    useEffect(() => {
        const unregisterListener = history.listen((location: History.Location<any>, action: History.Action) => {
            // Whenever the history object is updated, we will reinitialize the form.
            if (action === "POP" || action === "PUSH") {
                resetForm();
            }
        });
        return unregisterListener;
    }, []);

    useEffect(() => {
        if (initialized) {
            // We're already initialized.
            return;
        }

        const { search: browserQuery } = location;
        const queryForm: any = qs.parse(browserQuery.replace(/^\?/, ""));

        for (const [key, value] of Object.entries(queryForm)) {
            if (value === "true") {
                queryForm[key] = true;
            }

            if (value === "false") {
                queryForm[key] = false;
            }

            if (typeof value === "string" && Number.isInteger(parseInt(value, 10))) {
                queryForm[key] = parseInt(value, 10);
            }

            if (key.toLocaleLowerCase() === "search") {
                queryForm["query"] = queryForm[key];
            }

            if (key === "discussionID") {
                queryForm.domain = "discussions";
            }
        }

        const blockedKeys = ["needsResearch", "initialized"];
        blockedKeys.forEach((key) => {
            if (queryForm[key] !== undefined) {
                delete queryForm[key];
            }
        });

        if (typeof queryForm.scope === "string") {
            searchScope.setValue?.(queryForm.scope);
        }

        queryForm.initialized = true;

        updateForm(queryForm);
        // Only for first initialization.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialized]);
}

export default function ExportedSearchPage(props: IProps) {
    return (
        <LayoutProvider type={LayoutTypes.TWO_COLUMNS}>
            <Backgrounds />
            <SearchPage {...props} />
        </LayoutProvider>
    );
}
