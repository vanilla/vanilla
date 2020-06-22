/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { PublishStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";
import { ILinkPages } from "@library/navigation/SimplePagerModel";

export interface ISearchFormBase {
    domain: string;
    query: string;
    name?: string;
    authors?: IComboBoxOption[];
    startDate?: string;
    endDate?: string;
    page: number;
    types?: string[];
}

export type ISearchForm<T extends object = {}> = ISearchFormBase & T & Record<string | number, any>;

export interface ISearchRequestQuery extends Omit<ISearchForm, "authors" | "startDate" | "endDate"> {
    dateInserted?: string;
    insertUserIDs?: number[];
}

export interface ISearchResult {
    url: string;
    body: string;
    name: string;
    recordID: number;
    recordType: string;
    type: string;
    score?: number;
    breadcrumbs: ICrumb[];
    status?: PublishStatus;
    image?: {
        url: string;
        alt: string;
    };
    dateInserted: string;
    insertUserID: number;
    insertUser: IUserFragment;
}

export interface ISearchResults {
    results: ISearchResult[];
    pagination: ILinkPages;
}
