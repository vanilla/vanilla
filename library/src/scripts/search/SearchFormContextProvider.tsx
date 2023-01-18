/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { TypeAllIcon } from "@library/icons/searchIcons";
import { FilterPanelAll } from "@library/search/panels/FilterPanelAll";
import { SearchActions } from "@library/search/SearchActions";
import { DEFAULT_CORE_SEARCH_FORM, INITIAL_SEARCH_STATE, searchReducer } from "@library/search/searchReducer";
import { ISearchForm, ISearchRequestQuery, ISearchSource } from "@library/search/searchTypes";
import {
    ALLOWED_GLOBAL_SEARCH_FIELDS,
    ALL_CONTENT_DOMAIN_NAME,
    MEMBERS_RECORD_TYPE,
    MEMBERS_DOMAIN_NAME,
    PLACES_DOMAIN_NAME,
} from "@library/search/searchConstants";
import { t } from "@vanilla/i18n";
import React, { ReactNode, useCallback, useContext, useEffect, useReducer, useState } from "react";
import merge from "lodash/merge";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useSearchScope } from "@library/features/search/SearchScopeContext";
import { getCurrentLocale } from "@vanilla/i18n";
import { SearchContext } from "./SearchContext";
import { SearchService, ISearchDomain, DEFAULT_SEARCH_SOURCE } from "./SearchService";
import PlacesSearchListing from "@library/search/PlacesSearchListing";
import { getSiteSection } from "@library/utility/appUtils";
import { getSearchAnalyticsData } from "@library/search/searchAnalyticsData";
import { useSearchSources } from "@library/search/SearchSourcesContextProvider";
import { stableObjectHash } from "@vanilla/utils";

interface IProps {
    children?: React.ReactNode;
}

export const SEARCH_LIMIT_DEFAULT = 10;
export const DOMAIN_SEARCH_LIMIT_DEFAULT = 10;

export function getGlobalSearchSorts(): ISelectBoxItem[] {
    return [
        {
            content: t("Best Match"),
            value: "relevance",
        },
        {
            content: t("Newest"),
            value: "-dateInserted",
        },
        {
            content: t("Oldest"),
            value: "dateInserted",
        },
    ];
}

export function SearchFormContextProvider(props: IProps) {
    const [state, dispatch] = useReducer(searchReducer, INITIAL_SEARCH_STATE);

    const { currentSource: searchSource } = useSearchSources();

    const getFilterComponentsForDomain = (domain: string) => {
        return SearchService.extraFilters.map((extraFilter, i) => {
            if (extraFilter.searchDomain === domain) {
                return <React.Fragment key={i}>{extraFilter.filterNode}</React.Fragment>;
            } else {
                return null;
            }
        });
    };

    const hasPlacesDomain = SearchService.pluggableDomains.map((domain) => domain.key).includes(PLACES_DOMAIN_NAME);

    const ALL_CONTENT_DOMAIN: ISearchDomain = {
        key: ALL_CONTENT_DOMAIN_NAME,
        name: "All",
        sort: 0,
        icon: <TypeAllIcon />,
        heading: hasPlacesDomain ? <PlacesSearchListing /> : null,
        // To be called when performing a search in the current domain. We need to be aware of PLACES_DOMAIN_NAME
        // here because the component <PlacesSearchListing /> is specific to Places Search and yet we want it in
        // all domains and we want to make the query to the places domain
        extraSearchAction: () => {
            if (hasPlacesDomain) {
                searchInDomain(PLACES_DOMAIN_NAME);
            }
        },
        PanelComponent: FilterPanelAll,
        getAllowedFields: () => {
            return ALLOWED_GLOBAL_SEARCH_FIELDS;
        },
        getRecordTypes: () => {
            // Gather all other domains, and return their types.
            const allTypes: string[] = [];
            for (const pluggableDomain of SearchService.pluggableDomains) {
                allTypes.push(...pluggableDomain.getRecordTypes());
            }

            return allTypes.filter((t) => t !== MEMBERS_RECORD_TYPE);
        },
        getSortValues: getGlobalSearchSorts,
        transformFormToQuery: (form: ISearchForm) => {
            const query: ISearchRequestQuery = { ...form };
            if (query.sort === "relevance") {
                delete query.sort;
            }
            return query;
        },
        getDefaultFormValues: () => {
            return {
                sort: "relevance",
            };
        },
        isIsolatedType: () => false,
    };

    const getDomains = () => {
        return [{ ...ALL_CONTENT_DOMAIN, name: t(ALL_CONTENT_DOMAIN.name) }, ...SearchService.pluggableDomains];
    };

    const getCurrentDomain = (): ISearchDomain => {
        return (
            getDomains().find((pluggableDomain) => {
                return pluggableDomain.key === state.form.domain;
            }) ?? ALL_CONTENT_DOMAIN
        );
    };

    const getDate = (form: ISearchForm): string | undefined => {
        let dateInserted: string | undefined;
        if (form.startDate && form.endDate) {
            if (form.startDate === form.endDate) {
                // Simple equality.
                dateInserted = form.startDate;
            } else {
                // Date range
                dateInserted = `[${form.startDate},${form.endDate}]`;
            }
        } else if (form.startDate) {
            // Only start date
            dateInserted = `>=${form.startDate}`;
        } else if (form.endDate) {
            // Only end date.
            dateInserted = `<=${form.endDate}`;
        }
        return dateInserted;
    };

    const makeFilterForm = (form: ISearchForm): ISearchForm => {
        const currentDomain = getCurrentDomain();
        const allowedFields = [...ALL_CONTENT_DOMAIN.getAllowedFields(), ...currentDomain.getAllowedFields()];
        return Object.fromEntries(allowedFields.map((field) => [field, form[field]])) as ISearchForm;
    };

    const searchScope = useSearchScope();
    const buildQuery = (form: ISearchForm): ISearchRequestQuery => {
        const filterForm = makeFilterForm(form);
        const currentDomain = getCurrentDomain();

        const allowedSorts = currentDomain.getSortValues().map((val) => val.value);
        const sort = !!form.sort && allowedSorts.includes(form.sort) ? form.sort : undefined;

        const commonQueryEntries = {
            ...filterForm,
            ...currentDomain.transformFormToQuery?.(filterForm),
            limit: SEARCH_LIMIT_DEFAULT,
            dateInserted: getDate(form),
            locale: getCurrentLocale(),
            collapse: true,
            sort,
            ...(form.offset && { offset: form.offset }),
        };
        if (searchScope.value?.value) {
            commonQueryEntries.scope = searchScope.value.value;
        }

        let finalQuery: ISearchRequestQuery;

        // FIXME: these following conditions should probably be moved to different domains' `transformFormToQuery` callbacks
        if (currentDomain.key === MEMBERS_DOMAIN_NAME) {
            finalQuery = {
                ...commonQueryEntries,
                scope: "site", // Force site domain for members.
                recordTypes: [MEMBERS_RECORD_TYPE],
                expand: [],
            };
        } else if (currentDomain.key === PLACES_DOMAIN_NAME) {
            // No recordTypes, only types (from form)
            finalQuery = {
                ...commonQueryEntries,
                expand: ["breadcrumbs", "image", "excerpt", "-body"],
            };
        } else if (currentDomain.hasSpecificRecord?.(form)) {
            finalQuery = {
                ...commonQueryEntries,
                expand: ["insertUser", "breadcrumbs", "image", "excerpt", "-body"],
            };
        } else {
            finalQuery = {
                ...commonQueryEntries,
                domain: form.domain,
                insertUserIDs:
                    form.authors && form.authors.length
                        ? form.authors.map((author) => author.value as number)
                        : undefined,
                recordTypes: currentDomain.getRecordTypes(),
                expand: ["insertUser", "breadcrumbs", "image", "excerpt", "-body"],
            };
        }

        const siteSection = getSiteSection();
        const siteSectionCategoryID = siteSection.attributes.categoryID;
        /**
         * finalQuery["categoryIDs"] could be a populated array, an empty array, or undefined
         */
        const hasCategoryIDs = !!(finalQuery["categoryIDs"] && finalQuery["categoryIDs"].length);
        if (!("categoryID" in finalQuery) && !hasCategoryIDs && siteSectionCategoryID > 0) {
            finalQuery.categoryID = siteSectionCategoryID;
            finalQuery.includeChildCategories = true;
        }

        // Filter out empty fields.
        Object.entries(finalQuery).forEach(([field, value]) => {
            if (value === "" || value === undefined) {
                delete finalQuery[field];
            }
        });

        return finalQuery;
    };

    /**
     * This state holds a stable hash of the form query and the source its been searched from
     * to be used to prevent duplicate events from firing
     */
    const [hashedSearchEvents, setHashedSearchEvents] = useState<number[]>([]);

    /**
     * Generate and store a hash representing the form query and the search source
     */
    const updateHashedEventStore = (form: ISearchForm, source: ISearchSource): void => {
        const hash = stableObjectHash({ query: form.query, domain: form.domain, key: source.key });
        setHashedSearchEvents((prevValues) => {
            return [...new Set([...prevValues, hash])];
        });
    };
    /**
     * Used to check if a search event has already been tracked
     * Will prevent multiple events from firing should a user flip between
     * source tabs without changing the search term, or if a user
     * spams the search button
     */
    const shouldDispatchAnalyticsEvent = (form: ISearchForm, source: ISearchSource): boolean => {
        const hash = stableObjectHash({ query: form.query, domain: form.domain, key: source.key });
        return !hashedSearchEvents.includes(hash);
    };

    const search = async () => {
        const { form } = state;

        dispatch(SearchActions.performSearchACs.started(form));

        try {
            const query = buildQuery(form);

            const result = await searchSource.performSearch(query, form?.pageURL);

            dispatch(
                SearchActions.performSearchACs.done({
                    params: form,
                    result,
                }),
            );

            /**
             * Search event tracking
             */
            // Check if we should dispatch an event, or if one has been dispatched already
            const shouldTrack = shouldDispatchAnalyticsEvent(form, searchSource);

            if (shouldTrack) {
                // Make sure to update the store, to prevent subsequent event dispatch
                updateHashedEventStore(form, searchSource);
                document.dispatchEvent(
                    new CustomEvent("pageViewWithContext", {
                        detail: getSearchAnalyticsData(form, result, {
                            key: searchSource.key,
                            label: searchSource.label,
                        }),
                    }),
                );
            }
        } catch (error) {
            dispatch(SearchActions.performSearchACs.failed({ params: form, error }));
        }
    };

    const searchInDomain = async (domain: string) => {
        // We only do this for our own community search
        if (searchSource.key === DEFAULT_SEARCH_SOURCE.key) {
            const { form } = state;
            const formWithDomain = { ...form, domain };

            dispatch(SearchActions.performDomainSearchACs.started(formWithDomain));

            const subTypes = SearchService.getSubTypes()
                .filter((subType) => subType.domain === domain)
                .map((subType) => subType.type);

            try {
                const query = {
                    ...buildQuery(formWithDomain),
                    limit: DOMAIN_SEARCH_LIMIT_DEFAULT,
                    types: subTypes,
                    recordTypes: [],
                };

                const result = await searchSource.performSearch(query);

                dispatch(
                    SearchActions.performDomainSearchACs.done({
                        params: formWithDomain,
                        result,
                    }),
                );
            } catch (error) {
                dispatch(SearchActions.performDomainSearchACs.failed({ params: formWithDomain, error }));
            }
        }
    };

    const updateForm = useCallback((update: Partial<ISearchForm>) => {
        dispatch(SearchActions.updateSearchFormAC(update));
    }, []);

    const resetForm = useCallback(() => {
        dispatch(SearchActions.resetFormAC());
    }, []);

    const getDefaultFormValues = () => {
        const domainDefaults = getDomains().map((domain) => domain.getDefaultFormValues?.() ?? {});
        const merged = merge({}, DEFAULT_CORE_SEARCH_FORM, ...domainDefaults);
        return merged;
    };

    return (
        <SearchContext.Provider
            value={{
                getFilterComponentsForDomain,
                updateForm,
                results: state.results,
                domainSearchResponse: state.domainSearchResponse,
                form: state.form,
                search,
                searchInDomain,
                getDomains,
                getCurrentDomain,
                getDefaultFormValues,
                resetForm,
            }}
        >
            {props.children}
        </SearchContext.Provider>
    );
}
