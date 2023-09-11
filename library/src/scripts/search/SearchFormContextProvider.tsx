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
    MEMBERS_RECORD_TYPE,
    ALL_CONTENT_DOMAIN_KEY,
} from "@library/search/searchConstants";
import { t } from "@vanilla/i18n";
import React, { useCallback, useMemo, useReducer, useState } from "react";
import merge from "lodash/merge";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useSearchScope } from "@library/features/search/SearchScopeContext";
import { getCurrentLocale } from "@vanilla/i18n";
import { SearchContext } from "./SearchContext";
import { getSearchAnalyticsData } from "@library/search/searchAnalyticsData";
import { useSearchSources } from "@library/search/SearchSourcesContextProvider";
import { stableObjectHash } from "@vanilla/utils";
import { dateRangeToString } from "@library/search/SearchUtils";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain";
import SearchDomain from "@library/search/SearchDomain";

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
    const { sources, currentSource, setCurrentSource } = useSearchSources();

    const currentSourceDomains = currentSource?.domains ?? [];

    const { children } = props;

    // This is special "SearchDomain", that aggregates all the SearchDomains registered to a SearchSource.
    // It is used only if a SearchSource has multiple SearchDomains. (e.g. for Community)
    const ALL_CONTENT_DOMAIN = useMemo<SearchDomain>(
        () =>
            new (class AllContentDomain extends SearchDomain<{ startDate?: string; endDate?: string }> {
                public key = ALL_CONTENT_DOMAIN_KEY;
                public sort = 0;

                public get name() {
                    return t("All");
                }

                public icon = (<TypeAllIcon />);

                public subTypes = currentSourceDomains.map(({ subTypes }) => subTypes).flat();

                public addSubType = () => {
                    throw new Error("ALL_CONTENT_DOMAIN does not support adding subtypes");
                };

                public PanelComponent = FilterPanelAll;

                public getAllowedFields = () => ["name", "authors", "insertUserIDs", "startDate", "endDate"];

                public get recordTypes() {
                    return currentSourceDomains // Gather all other domains, and return their types.
                        .map(({ recordTypes }) => recordTypes)
                        .flat()
                        .filter((t) => t !== MEMBERS_RECORD_TYPE);
                }

                public get sortValues() {
                    return getGlobalSearchSorts();
                }

                public transformFormToQuery(form: ISearchForm<{ startDate?: string; endDate?: string }>) {
                    const query: ISearchRequestQuery<{ startDate?: string; endDate?: string }> = { ...form };

                    if (query.sort === "relevance") {
                        delete query.sort;
                    }
                    query.expand = ["insertUser", "breadcrumbs", "image", "excerpt", "-body"];
                    query.dateInserted = dateRangeToString({ start: form.startDate, end: form.endDate });
                    query.startDate = undefined;
                    query.endDate = undefined;
                    if (form.authors && form.authors.length) {
                        query.insertUserIDs = form.authors.map((author) => author.value as number);
                    }
                    return query;
                }

                public defaultFormValues = {
                    sort: "relevance",
                };
            })(),
        [currentSourceDomains],
    );

    const domains =
        currentSourceDomains.filter(({ isIsolatedType }) => !isIsolatedType).length > 1
            ? [ALL_CONTENT_DOMAIN, ...currentSourceDomains]
            : currentSourceDomains;

    const [state, dispatch] = useReducer(searchReducer, {
        ...INITIAL_SEARCH_STATE,
        form: {
            ...INITIAL_SEARCH_STATE.form,
            domain: ALL_CONTENT_DOMAIN_KEY,
        },
    });

    const { hasPermission } = usePermissionsContext();

    const currentDomain = useMemo((): SearchDomain => {
        return domains.length === 1
            ? domains[0]
            : domains.find(({ key }) => key === state.form.domain) ?? ALL_CONTENT_DOMAIN;
    }, [domains, currentSource, state.form.domain]);

    const makeFilterForm = (form: ISearchForm): ISearchForm => {
        const queryDomain = domains.find(({ key }) => key == form.domain) ?? currentDomain;

        const allowedFields = Array.from(
            new Set([...ALLOWED_GLOBAL_SEARCH_FIELDS, ...(queryDomain.getAllowedFields(hasPermission) ?? [])]),
        );

        const filterForm = Object.fromEntries(
            allowedFields
                .map((field) => [field, form[field]])
                .filter(([_key, val]) => {
                    return val !== "" && val !== undefined;
                }),
        ) as ISearchForm;

        return filterForm;
    };

    const searchScope = useSearchScope();
    const buildQuery = (form: ISearchForm): ISearchRequestQuery => {
        const filterForm = makeFilterForm(form);
        const queryDomain = domains.find(({ key }) => key == form.domain) ?? currentDomain;

        const allowedSorts = (queryDomain.sortValues ?? []).map((val) => val.value);
        const sort = !!form.sort && allowedSorts.includes(form.sort) ? form.sort : undefined;

        const searchForm: ISearchForm = {
            ...filterForm,
            collapse: true,
            recordTypes: queryDomain.recordTypes,
            scope: searchScope.value?.value ?? undefined,
            sort,
        };

        let query = {
            ...(queryDomain.transformFormToQuery?.(searchForm) ?? searchForm),
        } as ISearchRequestQuery;

        // Filter out empty fields.
        Object.entries(query).forEach(([field, value]) => {
            if (value === "" || value === undefined) {
                delete query[field];
            }
        });

        return {
            ...query,
            limit: SEARCH_LIMIT_DEFAULT,
            locale: getCurrentLocale(),
            offset: form.offset,
        };
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

        if (currentSource) {
            dispatch(SearchActions.performSearchACs.started(form));

            // When searching the Community "All Content" domain, we also want to search Places.
            if (
                currentDomain.key === ALL_CONTENT_DOMAIN.key &&
                currentSourceDomains.some((domain) => domain.key == PLACES_SEARCH_DOMAIN.key)
            ) {
                searchInDomain(PLACES_SEARCH_DOMAIN.key);
            }

            try {
                const query = buildQuery(form);
                const result = await currentSource.performSearch(query, form?.pageURL);

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
                const shouldTrack = shouldDispatchAnalyticsEvent(form, currentSource);

                if (shouldTrack) {
                    // Make sure to update the store, to prevent subsequent event dispatch
                    updateHashedEventStore(form, currentSource);
                    document.dispatchEvent(
                        new CustomEvent("pageViewWithContext", {
                            detail: getSearchAnalyticsData(form, result, {
                                key: currentSource.key,
                                label: currentSource.label,
                            }),
                        }),
                    );
                }
            } catch (error) {
                dispatch(SearchActions.performSearchACs.failed({ params: form, error }));
            }
        }
    };

    const searchInDomain = async (domainKey: string) => {
        if (currentSource) {
            const { form } = state;
            const formWithDomain = { ...form, domain: domainKey };

            const domain = domains.find(({ key }) => key === domainKey);

            dispatch(SearchActions.performDomainSearchACs.started(formWithDomain));

            const subTypes = (domain?.subTypes ?? []).map((subType) => subType.type);

            try {
                const query = {
                    ...buildQuery(formWithDomain),
                    limit: DOMAIN_SEARCH_LIMIT_DEFAULT,
                    types: subTypes,
                    recordTypes: undefined,
                };

                const result = await currentSource.performSearch(query);

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

    const updateForm = useCallback(
        (update: Partial<ISearchForm>) => {
            let updatedForm = {
                ...state.form,
                //Reset page on new searches. Pagination buttons can override this behaviour by passing a page option to this function
                page: undefined,
                ...update,
            };

            if ("domain" in update) {
                const nextDomain = domains.find(({ key }) => key === updatedForm.domain);
                const nextDomainAllowedFields = Array.from(
                    new Set([...ALLOWED_GLOBAL_SEARCH_FIELDS, ...(nextDomain?.getAllowedFields(hasPermission) ?? [])]),
                );

                const nextDomainAllowedSortValues = (nextDomain?.sortValues ?? []).map((val) => val.value);

                for (let key in updatedForm) {
                    if (!["initialized"].includes(key)) {
                        if (
                            !nextDomainAllowedFields.includes(key) ||
                            (key === "sort" &&
                                !!updatedForm[key] &&
                                !nextDomainAllowedSortValues.includes(updatedForm[key]!))
                        ) {
                            updatedForm[key] = undefined;
                        }
                    }
                }
            }

            dispatch(SearchActions.updateSearchFormAC(updatedForm));
        },
        [domains, currentDomain, state.form],
    );

    const resetForm = useCallback(() => {
        dispatch(SearchActions.resetFormAC());
    }, []);

    const defaultFormValues = useMemo<ISearchForm>(() => {
        const domainDefaults = currentDomain.defaultFormValues ?? {};
        const merged = merge({}, DEFAULT_CORE_SEARCH_FORM, domainDefaults);
        return merged;
    }, [currentDomain]);

    const handleSourceChange = useCallback(
        (newSourceKey: string) => {
            const nextSource = sources.find((source) => source.key === newSourceKey)!;
            const nextDomainKey = nextSource.domains.some(({ key }) => key === currentDomain.key)
                ? currentDomain.key
                : nextSource.domains.length === 1
                ? nextSource.domains[0]?.key ?? ALL_CONTENT_DOMAIN_KEY
                : ALL_CONTENT_DOMAIN_KEY;

            updateForm({
                domain: nextDomainKey,
            });

            setCurrentSource(newSourceKey);
        },
        [currentDomain.key, setCurrentSource, sources, updateForm],
    );

    return (
        <SearchContext.Provider
            value={{
                ...state,
                updateForm,
                search,
                domains,
                currentDomain,
                defaultFormValues,
                resetForm,
                handleSourceChange,
            }}
        >
            {children}
        </SearchContext.Provider>
    );
}
