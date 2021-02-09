/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ISearchForm, ISearchRequestQuery } from "@library/search/searchTypes";
import React from "react";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";

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
