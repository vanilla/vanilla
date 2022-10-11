/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ISearchForm, ISearchRequestQuery, ISearchResult, ISearchSource } from "@library/search/searchTypes";
import React from "react";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { t } from "@vanilla/i18n";
import Result, { IResult } from "@library/result/Result";

export class SearchService {
    private static _supportsExtensions = false;
    static setSupportsExtensions(supports: boolean) {
        this._supportsExtensions = supports;
    }
    static supportsExtensions(): boolean {
        return this._supportsExtensions;
    }
    static extraFilters = [] as IExtraFilter[];
    static addSearchFilter = (domain: string, filterNode: React.ReactNode) => {
        SearchService.extraFilters.push({
            searchDomain: domain,
            filterNode,
        });
    };

    static pluggableSources = [] as ISearchSource[];

    static addPluggableSource = function (source: ISearchSource) {
        if (!SearchService.pluggableSources.find((content) => content.key === source.key)) {
            SearchService.pluggableSources.push(source);
        }
    };

    static pluggableDomains = [] as Array<ISearchDomain<any, any, any>>; //FIXME: this collection should have a stronger type.

    static addPluggableDomain = function (domain: ISearchDomain<any, any, any>) {
        SearchService.pluggableDomains.push(domain);
    };

    static subTypes = {} as Record<string, ISearchSubType>;
    static addSubType = (subType: ISearchSubType) => {
        SearchService.subTypes[subType.type] = subType;
    };
    static getSubTypes = (): ISearchSubType[] => {
        return Object.values(SearchService.subTypes);
    };
    static getSubType = (type: string): ISearchSubType | null => {
        return SearchService.subTypes[type] ?? null;
    };
}

export const DEFAULT_SEARCH_SOURCE = new (class DefaultSearchSource implements ISearchSource {
    private abortController: AbortController;

    constructor() {
        this.abortController = new AbortController();
    }

    abort() {
        this.abortController.abort();
        this.abortController = new AbortController();
    }

    get key() {
        return "community";
    }

    get label() {
        return t("Community");
    }

    async performSearch(query: ISearchRequestQuery) {
        const response = await apiv2.get("/search", {
            params: query,
            signal: this.abortController.signal,
        });
        return {
            results: response.data.map((item) => {
                item.body = item.excerpt ?? item.body;
                return item;
            }),
            pagination: SimplePagerModel.parseHeaders(response.headers),
        };
    }
})();

SearchService.addPluggableSource(DEFAULT_SEARCH_SOURCE);

interface ISearchSubType {
    domain?: string;
    recordType: string;
    icon: React.ReactNode;
    type: string;
    label: string;
}
interface IExtraFilter {
    searchDomain: string;
    filterNode: React.ReactNode;
}

export interface ISearchDomain<
    ExtraFormValues extends object = {},
    ResultType extends object = ISearchResult,
    ResultComponentProps extends object = IResult,
> {
    key: string;
    name: string;
    sort: number; // The order the panel appears from left to right
    icon: React.ReactNode;
    heading?: React.ReactNode;
    extraSearchAction?(): void;
    getName?(): string;
    PanelComponent: React.ComponentType<any>;
    resultHeader?: React.ReactNode;
    getAllowedFields(): string[];
    getRecordTypes(): string[];
    transformFormToQuery?(form: ISearchForm<ExtraFormValues>): Partial<ISearchRequestQuery<ExtraFormValues>>;
    getDefaultFormValues?(): Partial<ISearchForm<ExtraFormValues>>; //fixme: these don't seem to have any effect on form fields' initial state
    isIsolatedType(): boolean;
    getSortValues(): ISelectBoxItem[];
    ResultComponent?: React.ComponentType<ResultComponentProps>;
    mapResultToProps?: (searchResult: ResultType) => ResultComponentProps;
    ResultWrapper?: React.ComponentType<any>;
    MetaComponent?: React.ComponentType<any>;
    hasSpecificRecord?(form: ISearchForm<ExtraFormValues>): boolean;
    getSpecificRecord?(form: ISearchForm<ExtraFormValues>): number;
    SpecificRecordPanel?: React.ComponentType<any>;
    SpecificRecordComponent?: React.ComponentType<any>;
    showSpecificRecordCrumbs?(): boolean; // We could later make this into a config object
}
