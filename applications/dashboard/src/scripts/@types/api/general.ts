/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

export const enum LoadStatus {
    PENDING = "PENDING",
    LOADING = "LOADING",
    SUCCESS = "SUCCESS",
    ERROR = "ERROR",
}

interface IPendingValue {
    status: LoadStatus.PENDING;
}

interface ILoadingValue<T> {
    status: LoadStatus.LOADING;
    data?: T;
    error?: IApiError;
}

interface ISuccessValue<T> {
    status: LoadStatus.SUCCESS;
    data: T;
    error?: undefined;
}

interface IErrorValue<T> {
    status: LoadStatus.ERROR;
    error: IApiError;
    data?: T;
}

export type ILoadable<T> = IPendingValue | ILoadingValue<T> | ISuccessValue<T> | IErrorValue<T>;

export interface IApiResponse<DataType = any> {
    data: DataType;
    status: number;
    headers: any;
}

export interface IFieldError {
    message: string; // translated message
    code: string; // translation code
    status: number; // HTTP status
    field: string;
}

export interface IApiError {
    message: string;
    status: number;
    errors?: {
        [key: string]: IFieldError[];
    };
}
