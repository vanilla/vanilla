/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import SearchBar from "@library/features/search/SearchBar";
import { searchBarClasses } from "@library/features/search/SearchBar.styles";
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
import Banner from "@library/banner/Banner";
import { useSearchForm } from "@library/search/SearchContext";
import { useLastValue } from "@vanilla/react-utils";
import classNames from "classnames";
import debounce from "lodash/debounce";
import qs from "qs";
import React, { useCallback, useEffect, useMemo } from "react";
import { useLocation, useHistory } from "react-router";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { SectionProvider, useSection } from "@library/layout/LayoutContext";
import PanelWidget from "@library/layout/components/PanelWidget";
import PanelWidgetHorizontalPadding from "@library/layout/components/PanelWidgetHorizontalPadding";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
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
import moment from "moment";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { useSearchSources } from "@library/search/SearchSourcesContextProvider";
import { DEFAULT_SEARCH_SOURCE } from "@library/search/SearchService";
import { ALL_CONTENT_DOMAIN_NAME } from "@library/search/searchConstants";

interface IProps {
    placeholder?: string;
}

function SearchPage(props: IProps) {
    const { form, updateForm, search, results, getDomains, getCurrentDomain, getDefaultFormValues } =
        useSearchForm<{}>();

    const { isCompact } = useSection();
    const classes = pageTitleClasses();
    useInitialQueryParamSync();

    const { sources, currentSource, setCurrentSource } = useSearchSources();
    const lastSourceKey = useLastValue(currentSource.key);

    const currentSourceIsCommunity = currentSource.key === DEFAULT_SEARCH_SOURCE.key;

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

    const rightTopContent = useMemo<React.ReactNode>(() => {
        if (hasSpecificRecord) {
            return currentDomain.SpecificRecordPanel ?? null;
        }
        if (currentSource?.queryFilterComponent) {
            return currentSource.queryFilterComponent ?? null;
        }
        return currentFilter;
    }, [
        currentDomain.SpecificRecordPanel,
        currentFilter,
        currentSource.queryFilterComponent,
        hasSpecificRecord,
        isCompact,
    ]);

    const { needsResearch } = form;
    useEffect(() => {
        // Trigger new search
        if (
            needsResearch ||
            (lastScope && lastScope !== scope) ||
            (lastSourceKey && lastSourceKey !== currentSource.key)
        ) {
            search();
            currentDomain.extraSearchAction?.();
        }
    }, [search, needsResearch, lastScope, scope, currentDomain, lastSourceKey, currentSource.key]);

    const domains = getDomains();
    const sortedNonIsolatedDomains = domains
        .filter((domain) => !domain.isIsolatedType())
        .sort((a, b) => a.sort - b.sort);

    const availableDomainKeys = currentSource.searchableDomainKeys ?? domains.map(({ key }) => key);

    const handleSourceChange = useCallback(
        (newSourceKey: string) => {
            const nextSource = sources.find((source) => source.key === newSourceKey)!;
            const nextAvailableDomainKeys = nextSource.searchableDomainKeys ?? domains.map(({ key }) => key);

            updateForm({
                // reset page so pagination doesn't carry over from one source to another.
                page: 1,
                ...(nextAvailableDomainKeys.includes(currentDomain.key)
                    ? //don't change the domain, if the new source supports the current domain.
                      {}
                    : // change to the new source's default domain, if it exists, or fall back to searching all domains.
                      {
                          domain: nextSource.defaultDomainKey ?? ALL_CONTENT_DOMAIN_NAME,
                      }),
            });

            setCurrentSource(newSourceKey);
        },
        [currentDomain.key, setCurrentSource, sources, updateForm],
    );

    const sortAndPaginationContent = useMemo(() => {
        return (
            <SortAndPaginationInfo
                pages={results.data?.pagination}
                sortValue={form.sort}
                onSortChange={(newSort) => updateForm({ sort: newSort })}
                sortOptions={currentDomain?.getSortValues() ?? currentSource?.sortOptions ?? []}
            />
        );
    }, [currentDomain, form.sort, results, updateForm, currentSource]);

    let mainBottomContent = (
        <>
            {sortAndPaginationContent}
            <SearchPageResults />
        </>
    );

    if (sources.length > 1) {
        mainBottomContent = (
            <Tabs
                defaultTabIndex={sources.map(({ key }) => key).indexOf(currentSource.key)}
                includeVerticalPadding={false}
                includeBorder={false}
                largeTabs
                tabType={TabsTypes.BROWSE}
                data={sources.map((source) => ({
                    tabID: source.key,
                    label: source.label,
                    contents: <SearchPageResults />,
                }))}
                onChange={({ tabID: newSourceKey }) => {
                    handleSourceChange(`${newSourceKey!}`);
                }}
                extraButtons={sortAndPaginationContent}
            />
        );
    }

    return (
        // Add a context provider so that smartlinks within search use dynamic navigation.
        <LinkContextProvider linkContexts={[formatUrl("/search", true)]}>
            <DocumentTitle title={form.query ? `${form.query}` : t("Search Results")}>
                <TitleBar title={t("Search")} />
                <Banner isContentBanner />
                <Container>
                    <QueryString
                        value={{
                            ...form,
                            initialized: undefined,
                            scope,
                            needsResearch: undefined,
                            source: currentSource.key,
                            pageURL: undefined,
                            offset: undefined,
                        }}
                        defaults={getDefaultFormValues()}
                    />
                    <SectionTwoColumns
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
                                        title={t("Search")}
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
                                                value={`${form.query}`}
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
                                                buttonType={ButtonTypes.PRIMARY}
                                                needsPageTitle={false}
                                                overwriteSearchBar={{
                                                    preset: SearchBarPresets.BORDER,
                                                }}
                                            />
                                        </div>
                                        {hasSpecificRecord && hasSpecificRecordID && (
                                            <SpecificRecordComponent discussionID={specificRecordID} />
                                        )}
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
                                                    disabled: !availableDomainKeys.includes(domain.key),
                                                };
                                            })}
                                            endFilters={domains
                                                .filter((domain) => domain.isIsolatedType())
                                                .map((domain) => {
                                                    return {
                                                        label: domain.name,
                                                        icon: domain.icon,
                                                        data: domain.key,
                                                        disabled: !availableDomainKeys.includes(domain.key),
                                                    };
                                                })}
                                        />
                                    )}
                                    {currentSourceIsCommunity &&
                                        PlacesSearchTypeFilter.searchTypes.length > 0 &&
                                        currentDomain.heading}
                                </PanelWidget>
                                {isCompact && currentSourceIsCommunity && (
                                    <PanelWidgetHorizontalPadding>
                                        <Drawer title={t("Filter Results")}>
                                            {currentSourceIsCommunity ? currentFilter : rightTopContent}
                                        </Drawer>
                                    </PanelWidgetHorizontalPadding>
                                )}
                            </>
                        }
                        mainBottom={<PanelWidgetHorizontalPadding>{mainBottomContent}</PanelWidgetHorizontalPadding>}
                        secondaryTop={!isCompact && <PanelWidget>{rightTopContent}</PanelWidget>}
                    />
                </Container>
            </DocumentTitle>
        </LinkContextProvider>
    );
}

function useInitialQueryParamSync() {
    const { updateForm, resetForm, form } = useSearchForm<{}>();
    const history = useHistory();
    const location = useLocation();
    const searchScope = useSearchScope();

    const { sources, setCurrentSource } = useSearchSources();

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
        const queryForm: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });

        for (const [key, value] of Object.entries(queryForm)) {
            if (value === "true") {
                queryForm[key] = true;
            }

            if (value === "false") {
                queryForm[key] = false;
            }

            if (
                // turn pure integer values into numbers.
                typeof value === "string" &&
                value.match(/^[\d]*$/)
            ) {
                let intVal = parseInt(value, 10);
                if (!Number.isNaN(intVal)) {
                    queryForm[key] = intVal;
                }
            }

            if (key.toLocaleLowerCase() === "search") {
                queryForm["query"] = queryForm[key];
            }

            if (key === "discussionID") {
                queryForm.domain = "discussions";
            }

            if (key === "source") {
                queryForm.source = queryForm[key];
            }
        }

        const blockedKeys = ["needsResearch", "initialized", "pageURL", "offset"];
        blockedKeys.forEach((key) => {
            if (queryForm[key] !== undefined) {
                delete queryForm[key];
            }
        });

        if (typeof queryForm.scope === "string") {
            searchScope.setValue?.(queryForm.scope);
        }

        if (typeof queryForm.source === "string" && sources.find(({ key }) => key === queryForm.source)) {
            setCurrentSource(queryForm.source);
        }

        queryForm.initialized = true;

        updateForm(queryForm);
        // Only for first initialization.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialized]);
}

export default function ExportedSearchPage(props: IProps) {
    return (
        <SectionProvider type={SectionTypes.TWO_COLUMNS}>
            <Backgrounds />
            <SearchPage {...props} />
        </SectionProvider>
    );
}
