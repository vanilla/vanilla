/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ISearchForm, ISearchRequestQuery, ISearchSource } from "@library/search/searchTypes";
import React from "react";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { t } from "@vanilla/i18n";

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
            SearchService.createNewController(source.key);
        }
    };

    // Keep a record of abort controllers by key (because we can only use it once)
    static sourceControllers: Record<ISearchSource["key"], AbortController> = {};

    /**
     * Create a new controller for a specific key. If you use a controller,
     * you MUST create a new one or subsequent network requests will not fire.
     */
    static createNewController = function (key: ISearchSource["key"]) {
        SearchService.sourceControllers[key] = new AbortController();
    };

    static pluggableDomains = [] as ISearchDomain[];

    static addPluggableDomain = (domain: ISearchDomain) => {
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

export const DEFAULT_SEARCH_SOURCE: ISearchSource = {
    key: "community",
    getLabel: () => t("Community"),
    performSearch: async function searchVanilla(query) {
        const response = await apiv2.get("/search", {
            params: query,
            signal: SearchService.sourceControllers["community"].signal,
        });
        return {
            results: response.data.map((item) => {
                item.body = item.excerpt ?? item.body;
                return item;
            }),
            pagination: SimplePagerModel.parseHeaders(response.headers),
        };
    },
};

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

export interface ISearchDomain {
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
    transformFormToQuery(form: Partial<ISearchForm>): Partial<ISearchRequestQuery>;
    getDefaultFormValues(): Partial<ISearchForm>;
    isIsolatedType(): boolean;
    getSortValues(): ISelectBoxItem[];
    ResultComponent: React.ComponentType<any>;
    ResultWrapper?: React.ComponentType<any>;
    MetaComponent?: React.ComponentType<any>;
    hasSpecificRecord?(form: Partial<ISearchForm>): boolean;
    getSpecificRecord?(form: Partial<ISearchForm>): number;
    SpecificRecordPanel?: React.ComponentType<any>;
    SpecificRecordComponent?: React.ComponentType<any>;
    showSpecificRecordCrumbs?(): boolean; // We could later make this into a config object
}
