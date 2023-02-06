/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { AxiosError, AxiosResponse } from "axios";
import { IUserFragment } from "@library/@types/api/users";
import { RecordID } from "@vanilla/utils";
import { ImageSourceSet } from "@library/utility/appUtils";
import { IFieldError } from "@vanilla/json-schema-forms";

export enum LoadStatus {
    PENDING = "PENDING",
    LOADING = "LOADING",
    SUCCESS = "SUCCESS",
    ERROR = "ERROR",
}

export type Loadable<T, E = any> =
    | {
          status: LoadStatus.PENDING | LoadStatus.LOADING;
          error?: undefined;
          data?: undefined;
      }
    | {
          status: LoadStatus.SUCCESS;
          error?: undefined;
          data: T;
      }
    | {
          status: LoadStatus.ERROR;
          error: E;
          data?: undefined;
      };

export interface ILoadable<T = never, E = IApiError> {
    status: LoadStatus;
    error?: E;
    data?: T;
}

export interface IApiResponse<DataType = any> {
    data: DataType;
    status: number;
    headers?: any;
}

// Moved
export type { IFieldError };

export interface IServerError {
    message: string;
    status: number;
    errors?: {
        [key: string]: IFieldError[];
    };
}

export interface IApiError extends AxiosError, IServerError {
    response: AxiosResponse<IServerError | null>;
}

interface IMultiType<T> {
    recordType: T;
    recordID: number;
}

export type MultiTypeRecord<T, Subtract extends keyof T, TypeName extends string> = Omit<T, Subtract> &
    IMultiType<TypeName>;

/**
 * Require one of two properties ona given interface
 *
 * https://stackoverflow.com/questions/40510611/typescript-interface-require-one-of-two-properties-to-exist
 */
export type RequireAtLeastOne<T, Keys extends keyof T = keyof T> = Pick<T, Exclude<keyof T, Keys>> &
    {
        [K in Keys]-?: Required<Pick<T, K>> & Partial<Pick<T, Exclude<Keys, K>>>;
    }[Keys];

export interface INavigationItemBadge {
    type: navigationItemBadgeType;
    text: string;
    url?: string;
}

export enum navigationItemBadgeType {
    TEXT = "text",
    VIEW = "view",
}

export interface INavigationItem {
    name: string;
    url?: string;
    parentID: RecordID;
    recordID: RecordID;
    sort: number | null;
    recordType: string;
    isLink?: boolean;
    badge?: INavigationItemBadge;
}

export interface IApiDateInfo {
    insertUserID: number;
    insertDate: string;
    updateUserID: number;
    updateDate: string;
}

export interface IApiDateInfoExpanded extends IApiDateInfo {
    insertUser: IUserFragment;
    updateUser: IUserFragment;
}

export interface INavigationTreeItem extends INavigationItem {
    children: INavigationTreeItem[];
}

export interface ILinkGroup {
    category: INavigationItem;
    items: INavigationItem[];
}

export interface ILinkListData {
    groups: ILinkGroup[];
    ungroupedItems: INavigationItem[];
}

export enum PublishStatus {
    DELETED = "deleted",
    UNDELETED = "undeleted",
    PUBLISHED = "published",
}

export enum Format {
    TEXT = "text",
    TEXTEX = "textex",
    MARKDOWN = "markdown",
    WYSIWYG = "wysiwyg",
    HTML = "html",
    BBCODE = "bbcode",
    RICH = "rich",
}

export interface IImage {
    url?: string;
    urlSrcSet?: ImageSourceSet;
    alt?: string;
}

export interface IFeaturedImage {
    display: boolean;
    fallbackImage?: string;
}
