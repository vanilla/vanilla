/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { PublishStatus } from "@library/@types/api/core";
import { IUserFragment } from "@library/@types/api/users";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { RecordID } from "@vanilla/utils";
import { ImageSourceSet } from "@library/utility/appUtils";
import SearchDomain from "@library/search/SearchDomain";
import { ITag } from "@library/features/tags/TagsReducer";

export interface ISearchSource<RequestQueryType = ISearchRequestQuery, SearchResultType = ISearchResult> {
    /** Key used to identify the search source */
    key: string;
    /** Translated label for the tab heading */
    label: string;
    /** Function to make the request and return the mapped results */
    performSearch: (query: RequestQueryType, endpointOverride?: string) => Promise<ISearchResponse<SearchResultType>>;
    /** Function to abort any in-progress request */
    abort?: AbortController["abort"];
    /** Sort options available to this search source */
    sortOptions?: ISelectBoxItem[];

    domains: SearchDomain[];

    loadDomains?: () => Promise<SearchDomain[]>;
}

interface ISearchFormBase {
    domain: string;
    query: RecordID;
    name?: string;
    authors?: IComboBoxOption[];
    page?: RecordID;
    /** Used to provide a full URL to refresh the form */
    pageURL?: string;
    offset?: RecordID;
    sort?: string;
    initialized?: boolean;
    needsResearch?: boolean;
    tags?: string[];

    recordTypes?: string[];
    scope?: string; // moved from ISearchRequestQuery
    collapse?: boolean; // moved from ISearchRequestQuery
}

export type ISearchForm<ExtraFormValues extends object = {}> = ISearchFormBase & ExtraFormValues;

export type ISearchRequestQuery<ExtraFormValues extends object = {}> = Omit<ISearchForm<ExtraFormValues>, "authors"> & {
    dateInserted?: string;
    insertUserIDs?: number[];

    limit?: RecordID;
    offset?: RecordID;
    locale?: string;
    expand?: string[];
    siteSectionID?: string;

    // fixme: this stuff needs to go
    categoryIDs?: RecordID[];
    categoryID?: RecordID;
    includeChildCategories?: boolean;
};

export interface ICountResult {
    count: number;
    labelCode: string;
}

export interface IBaseSearchResult {
    url: string;
    name: string;
    recordID: RecordID;
    recordType: string;
    dateInserted?: string;
    dateUpdated?: string;
    body?: string;
    type?: string;
    isForeign?: boolean;
    highlight?: string;
}

export interface IVanillaSearchResult extends IBaseSearchResult {
    image?: {
        url: string;
        alt: string;
        urlSrcSet?: ImageSourceSet;
    };
    siteDomain?: string;
    labelCodes?: string[];
    status?: PublishStatus;
    breadcrumbs?: ICrumb[];
    insertUser?: IUserFragment;
    subqueryMatchCount?: number;
    subqueryExtraParams?: Record<string, any>;
    tags?: ITag[];
}

export interface IArticlesSearchResult extends IVanillaSearchResult {
    excerpt?: string;
    updateUser?: IUserFragment;
}

export interface IPlacesSearchResult extends IVanillaSearchResult {
    counts?: ICountResult[];
}

export type ISearchResult = IBaseSearchResult & IVanillaSearchResult;

export interface ISearchResponse<SearchResultType = ISearchResult> {
    results: SearchResultType[];
    pagination: ILinkPages;
}
