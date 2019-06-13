/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError, IApiResponse } from "@library/@types/api/core";
import { AxiosResponse, AxiosInstance } from "axios";
import { logError } from "@vanilla/utils";
import { ThunkDispatch, ThunkAction } from "redux-thunk";
import { AnyAction } from "redux";
import { AsyncActionCreators } from "typescript-fsa";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

/**
 * Base class for creating redux actions.
 */
export default class ReduxActions<S extends ICoreStoreState = ICoreStoreState> {
    /**
     * Utility to create an action with our a without a payload of a given type.
     * The action generated can have its type narrowed in a reducer switch statement if the type T matches.
     *
     * @see ActionsUnion
     *
     * @param type The action type.
     * @param payload The payload data.
     */
    public static createAction<ActionType extends string>(type: ActionType): IAction<ActionType>;
    public static createAction<ActionType extends string, Payload>(
        type: ActionType,
        payload: Payload,
    ): IActionWithPayload<ActionType, Payload>;
    public static createAction<ActionType extends string, Payload>(type: ActionType, payload?: Payload) {
        return payload === undefined ? { type } : { type, payload };
    }

    /**
     * Create request, response, and error action creators.
     *
     * The dummy types are needed because typescript currently requires all generic types to be specified or all to be inferred. They cannot currently be mixed.
     *
     * @see https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
     *
     * @param requestType The string for the request type. This should be a unique constant.
     * @param responseType The string for the response type. This should be a unique constant.
     * @param errorType The string for the error type. This should be a unique constant.
     * @param dummyResponseType A placeholder to infer the type of the response. This isn't used for anything other than inferring a type.
     * @param dummyMetaType A placeholder to infer the type of the meta. This isn't used for anything other than inferringa  type.
     *
     * @example
     *
     * ```
     * const GET_THING_REQUEST = "GET_THING_REQUEST";
     * const GET_THING_RESPONSE = "GET_THING_RESPONSE";
     * const GET_THING_ERROR = "GET_THING_ERROR";
     * interface IThing { thing: string }
     * interface IThingOptions { page?: number }
     *
     * generateApiActionCreators(GET_THING_REQUEST, GET_THING_RESPONSE, GET_THING_ERROR, {} as IThing, {} as IThingOptions);
     * ```
     */
    public static generateApiActionCreators<
        RequestActionType extends string,
        ResponseActionType extends string,
        ErrorActionType extends string,
        ResponseDataType,
        Meta = any
    >(
        requestType: RequestActionType,
        responseType: ResponseActionType,
        errorType: ErrorActionType,
        dummyResponseType?: ResponseDataType,
        dummyMetaType?: Meta,
    ): {
        request: (meta?: Meta) => IApiAction<RequestActionType, Meta>;
        response: (
            payload: IApiResponse<ResponseDataType>,
            meta?: Meta,
        ) => IApiResponseAction<ResponseActionType, Meta, ResponseDataType>;
        error: (error: IApiError, meta?: Meta) => IApiErrorAction<ErrorActionType, Meta>;
    } {
        return {
            request: (meta: Meta) => ReduxActions.createApiRequestAction(requestType, meta),
            response: (response: IApiResponse<ResponseDataType>, meta: Meta) =>
                ReduxActions.createApiResponseAction(responseType, meta, response),
            error: (error: IApiError, meta: Meta) => ReduxActions.createApiErrorAction(errorType, meta, error),
        };
    }

    /**
     * Create an API request action. For use in createApiActions().
     *
     * @param type The action's type.
     * @param meta The type of the meta for the action.
     */
    private static createApiRequestAction<ActionType extends string, Meta>(
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
     * @param type The action's type.
     * @param meta The type of the meta for the action.
     * @param error An API error.
     */
    private static createApiErrorAction<ActionType extends string, Meta>(
        type: ActionType,
        meta: Meta,
        error: IApiError,
    ): IApiErrorAction<ActionType, Meta> {
        logError(error);
        return {
            type,
            meta,
            payload: error,
        };
    }

    /**
     * Create an API response action. For use in createApiActions().
     *
     * @param type The action's type.
     * @param meta The type of the meta for the action.
     * @param payload The shape of the IApiResponse data.
     */
    private static createApiResponseAction<ActionType extends string, Meta, ResponseDataType>(
        type: ActionType,
        meta: Meta,
        payload: IApiResponse<ResponseDataType>,
    ): IApiResponseAction<ActionType, Meta, ResponseDataType> {
        return {
            type,
            meta,
            payload,
        };
    }

    /**
     * Constructor for the redux actions.
     *
     * @param dispatch A redux dispatch function.
     * @param api An API instance.
     * @param getState Optionally override the getState method. (generally for testin purposes.)
     */
    constructor(protected dispatch: any, protected api: AxiosInstance, getState?: () => any) {
        if (getState) {
            this.getState = getState;
        }
    }

    /**
     * Bind dispatch to an action creator.
     */
    protected bindDispatch = <T extends (...args) => void>(actionCreator: T): T => {
        return ((...args) => {
            return this.dispatch(actionCreator.apply(this, args));
        }) as any;
    };

    /**
     * Generate a simple redux thunk for an API request using action creators.
     *
     * @param requestType The request method. Eg. post, patch, get, put.
     * @param endpoint The endpoint requested.
     * @param actionCreators Action creators generated from {@link ReduxActions.generateApiActionCreators()}
     * @param params A parameter object for the request. This will be serialized as a JSON body or query string.
     * @param meta
     */
    protected async dispatchApi<T>(
        requestType: RequestType,
        endpoint: string,
        actionCreators: ReturnType<typeof ReduxActions.generateApiActionCreators>,
        params: any,
        meta: any = {},
    ): Promise<AxiosResponse<T> | undefined> {
        meta = {
            ...params,
            ...meta,
        };
        this.dispatch(actionCreators.request(meta));
        try {
            const requestPromise =
                requestType === "get"
                    ? this.api.get(endpoint, { params })
                    : this.api[requestType as any](endpoint, params);
            const response: AxiosResponse<T> = await requestPromise;
            this.dispatch(actionCreators.response(response, meta));
            return response;
        } catch (apiError) {
            this.dispatch(actionCreators.error(apiError, meta));
        }
    }

    protected getState(): S {
        return this.dispatch((c, getState) => {
            return getState();
        });
    }
}

// Redux FSA

/**
 * It's either a promise, or it isn't
 */
type MaybePromise<Type> = Type | Promise<Type>;

/**
 * A redux-thunk with the params as the first argument.  You don't have to
 * return a promise; but, the result of the dispatch will be one.
 */
type AsyncWorker<Params, Succ, State, Extra = any> = (
    params: Params,
    dispatch: ThunkDispatch<State, Extra, AnyAction>,
    getState: () => State,
    extra: Extra,
) => MaybePromise<Succ>;

/** A function that takes parameters and returns a redux-thunk */
type ThunkActionCreator<Params, Result, State, Extra> = (
    params?: Params,
) => ThunkAction<Result, State, Extra, AnyAction>;

/**
 * Bind a redux-thunk to typescript-fsa async action creators
 * @param actionCreators The typescript-fsa async action creators
 * @param asyncWorker A redux-thunk with extra `params` as the first argument
 * @returns a ThunkActionCreator, the result of which you can pass to dispatch()
 */
export const bindThunkAction = <Params, Succ, Err, State, Extra = any>(
    actionCreators: AsyncActionCreators<Params, Succ, Err>,
    asyncWorker: AsyncWorker<Params, Succ, State, Extra>,
): ThunkActionCreator<Params, Promise<Succ>, State, Extra> => params => async (dispatch, getState, extra) => {
    try {
        dispatch(actionCreators.started(params!));
        const result = await asyncWorker(params!, dispatch, getState, extra);
        dispatch(actionCreators.done({ params: params!, result }));
        return result;
    } catch (error) {
        dispatch(actionCreators.failed({ params: params!, error }));
        throw error;
    }
};

// Action interfaces
export interface IAction<T extends string> {
    type: T;
}

export interface IActionWithPayload<T extends string, P> extends IAction<T> {
    payload: P;
    meta?: any;
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

interface IApiResponseAction<ActionType, Meta, ResponseDataType> extends IApiAction<ActionType, Meta> {
    payload: IApiResponse<ResponseDataType>;
}

type RequestType = "get" | "post" | "put" | "delete" | "patch";

// Utility to pull a group of action types out of an actions object
export type ActionsUnion<A extends IActionCreatorsMapObject> = ReturnType<A[keyof A]>;
