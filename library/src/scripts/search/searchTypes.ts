/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { PublishStatus } from "@library/@types/api/core";
import { IUserFragment, IUser } from "@library/@types/api/users";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";

export interface ISearchSource {
    /** Key used to identify the search source */
    key: string;
    /** Translated label for the tab heading */
    getLabel: () => string;
    /** Function to make the request and return the mapped results */
    performSearch: (query: ISearchRequestQuery) => Promise<ISearchResults>;
    /** Sort options available to this search source */
    sortOptions?: ISelectBoxItem[];
    /** The additional search filters this source should make available */
    queryFilterComponent?: React.ReactNode;
    /** The domains that can be searched using this source */
    searchableDomainKeys?: string[];
    /** The default domain searched using this source */
    defaultDomainKey?: string;
}

export interface ISearchFormBase {
    domain: string;
    query: string;
    name?: string;
    authors?: IComboBoxOption[];
    startDate?: string;
    endDate?: string;
    page: number;
    types?: string[];
    sort: string;
    initialized: boolean;
    needsResearch?: boolean;
    tags?: string[];
}

export type ISearchForm<T extends object = {}> = ISearchFormBase & T & Record<string | number, any>;

export interface ISearchRequestQuery extends Omit<ISearchForm, "authors" | "startDate" | "endDate"> {
    scope?: string;
    dateInserted?: string;
    insertUserIDs?: number[];
    collapse?: boolean;
}

export interface ICountResult {
    count: number;
    labelCode: string;
}

export interface ISearchResult {
    url: string;
    body: string;
    excerpt: string;
    name: string;
    recordID: number;
    recordType: string;
    type: string;
    score?: number;
    breadcrumbs: ICrumb[];
    labelCodes?: string[];
    status?: PublishStatus;
    image?: {
        url: string;
        alt: string;
    };
    dateUpdated: string | null;
    dateInserted: string;
    insertUserID?: number;
    insertUser?: IUserFragment;
    updateUserID?: number;
    updateUser?: IUserFragment;
    userInfo?: IUser;
    counts?: ICountResult[];
    isForeign?: boolean;
    discussionID?: number;
    subqueryMatchCount?: number;
    subqueryExtraParams?: Record<string, any>;
    searchScore?: number;
    siteID?: number;
    siteDomain?: string;
    highlight?: string;
    source?: string;
}

export interface ISearchResults {
    results: ISearchResult[];
    pagination: ILinkPages;
}
