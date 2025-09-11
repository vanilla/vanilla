/**
 * @copyright 2009-2024 Vanilla Forums Inc.
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
import DocumentTitle from "@library/routing/DocumentTitle";
import { SearchInFilter } from "@library/search/SearchInFilter";
import { SearchPageResults } from "@library/search/SearchPageResults";
import { SortAndPaginationInfo } from "@library/search/SortAndPaginationInfo";
// This new page must have our base reset in place.
import "@library/theming/reset";
import { t, formatUrl } from "@library/utility/appUtils";
import Banner from "@library/banner/Banner";
import { useSearchForm } from "@library/search/SearchFormContext";
import { useLastValue } from "@vanilla/react-utils";
import * as qs from "qs-esm";
import React, { ReactElement, useEffect, useMemo, useState } from "react";
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
import { AiSearchResultsPanel, AiSearchSourcesPanel } from "@library/search/AiSearchSummary";
import History from "history";
import { Backgrounds } from "@library/layout/Backgrounds";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { useSearchSources } from "@library/search/SearchSourcesContext";
import { ALL_CONTENT_DOMAIN_KEY } from "./searchConstants";
import PlacesSearchListing from "./PlacesSearchListing";
import PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";
import apiv2 from "@library/apiv2";

export function SearchPageContent() {
    const {
        form,
        updateForm,
        search,
        response,
        domainSearchResponse,
        handleSourceChange,
        domains,
        currentDomain,
        currentSource,
    } = useSearchForm<{}>();

    const { isCompact } = useSection();
    const { sources } = useSearchSources();

    let scope = useSearchScope().value?.value ?? SEARCH_SCOPE_LOCAL;
    if (currentDomain.isIsolatedType) {
        scope = SEARCH_SCOPE_LOCAL;
    }
    const lastScope = useLastValue(scope);

    const hasSpecificRecord = currentDomain.getSpecificRecordID?.(form) ?? false;
    const specificRecordID = hasSpecificRecord ? currentDomain.getSpecificRecordID?.(form) : undefined;

    const SpecificRecordFilter = hasSpecificRecord ? currentDomain.SpecificRecordPanelComponent ?? null : null;
    const SpecificRecordComponent = hasSpecificRecord ? currentDomain.SpecificRecordComponent ?? null : null;

    const rightTopContent: ReactElement | undefined = SpecificRecordFilter ? (
        <SpecificRecordFilter />
    ) : currentDomain.PanelComponent ? (
        form.initialized ? (
            <currentDomain.PanelComponent />
        ) : undefined
    ) : undefined;
    const { needsResearch } = form;
    useEffect(() => {
        if (needsResearch || (lastScope && lastScope !== scope)) {
            void search();
        }
    });

    const sortAndPaginationContent = useMemo(() => {
        return (
            <SortAndPaginationInfo
                pages={response.data?.pagination}
                sortValue={form.sort}
                onSortChange={(newSort) => updateForm({ sort: newSort })}
                sortOptions={currentDomain?.sortValues ?? currentSource?.sortOptions ?? []}
            />
        );
    }, [currentDomain, form.sort, response, updateForm, currentSource]);

    const sortedNonIsolatedDomains = domains.filter((domain) => !domain.isIsolatedType).sort((a, b) => a.sort - b.sort);
    const availableDomainKeys = domains.map(({ key }) => key);

    const hasPlacesDomain = availableDomainKeys.includes(PLACES_SEARCH_DOMAIN.key);

    const extraHeadingContent = (
        <>
            {!hasSpecificRecord && domains.length > 1 && (
                <SearchInFilter
                    setData={(newDomain) => {
                        updateForm({ domain: newDomain, page: undefined });
                    }}
                    activeItem={form.domain}
                    filters={sortedNonIsolatedDomains.map((domain) => {
                        return {
                            label: domain.name,
                            icon: domain.icon,
                            data: domain.key,
                        };
                    })}
                    endFilters={domains
                        .filter((domain) => domain.isIsolatedType)
                        .map((domain) => {
                            return {
                                label: domain.name,
                                icon: domain.icon,
                                data: domain.key,
                            };
                        })}
                />
            )}

            {currentDomain.key === ALL_CONTENT_DOMAIN_KEY && hasPlacesDomain ? (
                <PlacesSearchListing domainSearchResponse={domainSearchResponse} />
            ) : undefined}
        </>
    );

    const searchPageResultsContent = (
        <>
            {extraHeadingContent}
            {isCompact && !!rightTopContent && (
                <PanelWidgetHorizontalPadding>
                    <Drawer title={t("Filter Results")}>{rightTopContent}</Drawer>
                </PanelWidgetHorizontalPadding>
            )}
            {sources.length <= 1 && sortAndPaginationContent}
            <SearchPageResults />
        </>
    );

    // Track query for AI Search
    const [currentQuery, setCurrentQuery] = useState(form.query);

    useEffect(() => {
        if (form.query) {
            setCurrentQuery(typeof form.query === "string" ? form.query : "");
        }
    }, [form.initialized]);

    return (
        <Container>
            <SectionTwoColumns
                className="hasLargePadding"
                mainTop={
                    <>
                        <PanelWidget>
                            <PageHeading title={t("Search")} includeBackLink={false} />
                            <ConditionalWrap
                                condition={currentDomain.isIsolatedType}
                                component={EmptySearchScopeProvider}
                            >
                                <div className={searchBarClasses({}).standardContainer}>
                                    <SearchBar
                                        onChange={(newQuery) => updateForm({ query: newQuery })}
                                        value={`${form.query}`}
                                        onSearch={() => {
                                            setCurrentQuery(
                                                typeof form.query === "string" && form.query !== currentQuery
                                                    ? form.query
                                                    : "",
                                            );

                                            return search();
                                        }}
                                        isLoading={response.status === LoadStatus.LOADING}
                                        optionComponent={SearchOption}
                                        triggerSearchOnClear={true}
                                        titleAsComponent={t("Search")}
                                        disableAutocomplete={true}
                                        buttonType={ButtonTypes.PRIMARY}
                                        overwriteSearchBar={{
                                            preset: SearchBarPresets.BORDER,
                                        }}
                                    />
                                </div>
                                {!!SpecificRecordComponent && (
                                    <SpecificRecordComponent discussionID={specificRecordID} />
                                )}
                            </ConditionalWrap>

                            <AiSearchResultsPanel query={currentQuery as string} />
                        </PanelWidget>
                    </>
                }
                mainBottom={
                    <PanelWidgetHorizontalPadding>
                        {sources.length > 1 ? (
                            <Tabs
                                defaultTabIndex={
                                    currentSource ? sources.map(({ key }) => key).indexOf(currentSource.key) : 0
                                }
                                includeVerticalPadding={false}
                                includeBorder
                                largeTabs
                                tabType={TabsTypes.BROWSE}
                                data={sources.map((source) => ({
                                    tabID: source.key,
                                    label: source.label,
                                    contents: searchPageResultsContent,
                                }))}
                                onChange={async ({ tabID: newSourceKey }) => {
                                    await handleSourceChange(`${newSourceKey!}`);
                                }}
                                extraButtons={sortAndPaginationContent}
                            />
                        ) : (
                            <>{searchPageResultsContent}</>
                        )}
                    </PanelWidgetHorizontalPadding>
                }
                secondaryTop={
                    !isCompact &&
                    !!rightTopContent && (
                        <PanelWidget>
                            <AiSearchSourcesPanel />
                            {rightTopContent}
                        </PanelWidget>
                    )
                }
            />
        </Container>
    );
}

function useInitialQueryParamSync() {
    const { updateForm, resetForm, form } = useSearchForm<{}>();
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

    // Look up labels and names from IDs for filter options
    const hydrateFormOptions = async (queryForm: Record<string, any>) => {
        let modifiedQueryForm = { ...queryForm };

        const hydrateOptions = async (
            queryFormKey: string,
            apiEndpoint: string,
            idField: string,
            fields: string[],
            modifiedQueryForm: Record<string, any>,
        ) => {
            if (queryForm?.[queryFormKey]) {
                const ids = Array.isArray(queryForm[queryFormKey])
                    ? queryForm[queryFormKey]?.map((item) => item?.value)
                    : queryForm[queryFormKey]?.value;

                const requestData = await apiv2
                    .get(apiEndpoint, {
                        params: {
                            [idField]: ids,
                            fields,
                        },
                    })
                    .then((response) =>
                        response.data.reduce((acc, curr) => {
                            return {
                                ...acc,
                                [curr[idField]]: curr,
                            };
                        }, {}),
                    );

                if (Array.isArray(modifiedQueryForm[queryFormKey])) {
                    modifiedQueryForm[queryFormKey] = modifiedQueryForm[queryFormKey].map((item) => {
                        const data = requestData[item?.value];
                        if (data) {
                            return {
                                ...item,
                                label: data.name,
                                ...(data.tagCode && { tagCode: data.tagCode }),
                            };
                        }
                        return item;
                    });
                } else {
                    const existingValue = modifiedQueryForm[queryFormKey]?.value;
                    modifiedQueryForm[queryFormKey]["label"] = requestData[existingValue]?.name;
                }
            }
        };

        await hydrateOptions("authors", "/users", "userID", ["name", "userID"], modifiedQueryForm);
        await hydrateOptions("tagsOptions", "/tags", "tagID", ["name", "tagID", "tagCode"], modifiedQueryForm);
        await hydrateOptions(
            "knowledgeBaseOption",
            "/knowledge-bases",
            "knowledgeBaseID",
            ["name", "knowledgeBaseID"],
            modifiedQueryForm,
        );
        return modifiedQueryForm;
    };

    useEffect(() => {
        if (initialized) {
            // We're already initialized.
            return;
        }

        const initializeFormOnLoad = async () => {
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
                    value.match(/^[\d]*$/) &&
                    !value.match(/^0/)
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

            queryForm.initialized = true;

            const modifiedQueryForm = await hydrateFormOptions(queryForm);

            updateForm(modifiedQueryForm);
        };

        initializeFormOnLoad().catch((error) => {
            console.error("Error initializing form on load:", error);
        });
        // Only for first initialization.
    }, [initialized]);
}

export function SearchPage() {
    useInitialQueryParamSync();
    const { form } = useSearchForm<{}>();

    return (
        <SectionProvider type={SectionTypes.TWO_COLUMNS}>
            <Backgrounds />
            {/* Add a context provider so that smartlinks within search use dynamic navigation. */}
            <LinkContextProvider linkContexts={[formatUrl("/search", true)]}>
                <DocumentTitle title={form.query ? `${form.query}` : t("Search Results")}>
                    <TitleBar />
                    <Banner isContentBanner />
                    <SearchPageContent />
                </DocumentTitle>
            </LinkContextProvider>
        </SectionProvider>
    );
}

export default function SearchPageWithContext() {
    const location = useLocation();

    const queryString: any = qs.parse(location.search, { ignoreQueryPrefix: true });
    const initialSourceKey = typeof queryString.source === "string" ? (queryString.source as string) : undefined;
    const initialDomainKey = typeof queryString.domain === "string" ? (queryString.domain as string) : undefined;

    return (
        <SearchFormContextProvider
            initialSourceKey={initialSourceKey}
            initialFormState={initialDomainKey ? { domain: initialDomainKey } : undefined}
        >
            <SearchPage />
        </SearchFormContextProvider>
    );
}
