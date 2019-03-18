/**
 * State utility functions.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@library/apiv2";
import { AxiosResponse, AxiosError } from "axios";
import { IApiResponse, IApiError } from "@library/@types/api/core";

// Utility to pull a group of action types out of an actions object
export type ActionsUnion<A extends IActionCreatorsMapObject> = ReturnType<A[keyof A]>;

// Utility to create a generic action action.

/**
 * Utility to create an action with our a without a payload of a given type.
 * The action generated can have its type narrowed in a reducer switch statement if the type T matches.
 *
 * @deprecated
 * @see ActionsUnion
 *
 * @param type The action type.
 * @param payload The payload data.
 */
export function createAction<ActionType extends string>(type: ActionType): IAction<ActionType>;
export function createAction<ActionType extends string, Payload>(
    type: ActionType,
    payload: Payload,
): IActionWithPayload<ActionType, Payload>;
export function createAction<ActionType extends string, Payload>(type: ActionType, payload?: Payload) {
    return payload === undefined ? { type } : { type, payload };
}

/**
 * Create request, response, and error action creators.
 *
 * The dummy types are needed because typescript currently requires all generic types to be specified or all to be inferred. They cannot currently be mixed.
 *
 * @deprecated
 *
 * @see https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
 *
 * @param requestType The string for the request type. This should be a unique constant.
 * @param successType The string for the success type. This should be a unique constant.
 * @param errorType The string for the error type. This should be a unique constant.
 * @param dummyResponseType A placeholder to infer the type of the response. This isn't used for anything other than inferring a type.
 * @param dummyMetaType A placeholder to infer the type of the meta. This isn't used for anything other than inferringa  type.
 *
 * @example
 *
 * ```
 * const GET_THING_REQUEST = "GET_THING_REQUEST";
 * const GET_THING_SUCCESS = "GET_THING_SUCCESS";
 * const GET_THING_ERROR = "GET_THING_ERROR";
 * interface IThing { thing: string }
 * interface IThingOptions { page?: number }
 *
 * generateApiActionCreators(GET_THING_REQUEST, GET_THING_SUCCESS, GET_THING_ERROR, {} as IThing, {} as IThingOptions);
 * ```
 */
export function generateApiActionCreators<
    RequestActionType extends string,
    SuccessActionType extends string,
    ErrorActionType extends string,
    ResponseDataType,
    Meta = any
>(
    requestType: RequestActionType,
    successType: SuccessActionType,
    errorType: ErrorActionType,
    dummyResponseType?: ResponseDataType,
    dummyMetaType?: Meta,
): {
    request: (meta?: Meta) => IApiAction<RequestActionType, Meta>;
    success: (
        payload: IApiResponse<ResponseDataType>,
        meta?: Meta,
    ) => IApiSuccessAction<SuccessActionType, Meta, ResponseDataType>;
    error: (error: IApiError, meta?: Meta) => IApiErrorAction<ErrorActionType, Meta>;
} {
    return {
        request: (meta: Meta) => createApiRequestAction(requestType, meta),
        success: (response: IApiResponse<ResponseDataType>, meta: Meta) =>
            createApiSuccessAction(successType, meta, response),
        error: (error: IApiError, meta: Meta) => createApiErrorAction(errorType, meta, error),
    };
}

type GeneratedActionCreators = ReturnType<typeof generateApiActionCreators>;

// Thunk types
type RequestType = "get" | "post" | "put" | "delete" | "patch";
/**
 * @deprecated
 * @param requestType
 * @param endpoint
 * @param actionCreators
 * @param params
 */
export function apiThunk(
    requestType: RequestType,
    endpoint: string,
    actionCreators: GeneratedActionCreators,
    params: any,
) {
    return dispatch => {
        dispatch(actionCreators.request(params));
        return apiv2[requestType as any](endpoint, params)
            .then((response: AxiosResponse) => {
                dispatch(actionCreators.success(response, params));
                return response;
            })
            .catch((axiosError: AxiosError) => {
                const error = axiosError.response ? axiosError.response.data : (axiosError as any);
                dispatch(actionCreators.error(error));
            });
    };
}

// Action interfaces
export interface IAction<T extends string> {
    type: T;
}

export interface IActionWithPayload<T extends string, P> extends IAction<T> {
    payload: P;
}

export interface IActionCreator<T extends string> {
    (): IAction<T>;
}

type FunctionType = (...args: any[]) => any;
interface IActionCreatorsMapObject {
    [actionCreator: string]: FunctionType;
}

// API Action interfaces
interface IApiAction<ActionType, Meta> {
    type: ActionType;
    meta: Meta;
}

interface IApiErrorAction<ActionType, Meta> extends IApiAction<ActionType, Meta> {
    payload: IApiError;
}

interface IApiSuccessAction<ActionType, Meta, ResponseDataType> extends IApiAction<ActionType, Meta> {
    payload: IApiResponse<ResponseDataType>;
}

/**
 * Create an API request action. For use in createApiActions().
 *
 * @deprecated
 * @param type The action's type.
 * @param meta The type of the meta for the action.
 */
function createApiRequestAction<ActionType extends string, Meta>(
    type: ActionType,
    meta: Meta,
): IApiAction<ActionType, Meta> {
    return {
        type,
        meta,
    };
}

/**
 * Create an API error action. For use in createApiActions().
 *
 * @deprecated
 * @param type The action's type.
 * @param meta The type of the meta for the action.
 * @param error An API error.
 */
function createApiErrorAction<ActionType extends string, Meta>(
    type: ActionType,
    meta: Meta,
    error: IApiError,
): IApiErrorAction<ActionType, Meta> {
    return {
        type,
        meta,
        payload: error,
    };
}

/**
 * Create an API success action. For use in createApiActions().
 *
 * @deprecated
 * @param type The action's type.
 * @param meta The type of the meta for the action.
 * @param payload The shape of the IApiResponse data.
 */
function createApiSuccessAction<ActionType extends string, Meta, ResponseDataType>(
    type: ActionType,
    meta: Meta,
    payload: IApiResponse<ResponseDataType>,
): IApiSuccessAction<ActionType, Meta, ResponseDataType> {
    return {
        type,
        meta,
        payload,
    };
}
