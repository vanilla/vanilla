/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { AxiosError, AxiosResponse } from "axios";
import { IUserFragment } from "@library/@types/api/users";
import { RecordID } from "@vanilla/utils";

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

export interface IFieldError {
    message: string; // translated message
    code?: string; // translation code
    field: string;
    status?: number; // HTTP status
}

export interface IServerError {
    message: string;
    status: number;
    errors?: {
        [key: string]: IFieldError[];
    };
}

export interface IApiError extends AxiosError {
    response: AxiosResponse<IServerError | null>;
}

interface IMultiType<T> {
    recordType: T;
    recordID: number;
}

export type MultiTypeRecord<T, Subtract extends keyof T, TypeName extends string> = Omit<T, Subtract> &
    IMultiType<TypeName>;

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
