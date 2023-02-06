/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IError } from "@library/errorPages/CoreErrorMessages";
import { AxiosError, AxiosInstance, AxiosRequestConfig, AxiosResponse } from "axios";

/**
 * Abstraction over long running multi-requests. Generally used for large bulk actions.
 *
 * API endpoints must explicitly support this and will have 408 responses documented as a possible result.
 */
export class LongRunnerClient {
    private successHandler: LongRunnerSuccessHandler | null = null;
    private failHandler: LongRunnerFailedHandler | null = null;

    /**
     * Constructor for the action.
     *
     * @param apiClient The API client instance to use.
     */
    public constructor(private apiClient: AxiosInstance) {}

    /**
     * Run the request, polling it to completion, then return the final response.
     *
     * @param method The HTTP method to use.
     * @param url The URL to request.
     * @param body The body to pass in the request.
     */
    public async request(config: AxiosRequestConfig): Promise<AxiosResponse> {
        const response = await this.apiClient
            .request({
                ...config,

                // Add a tag so we know we initialized the request.
                [FROM_LONG_RUNNER as any]: true,
            })
            .catch(this.extractErrResponse);
        this.reportProgress(response);
        const handledResponse = await this.handleResponse(response, true);
        return handledResponse;
    }

    /**
     * Use the response handler from the middleware as an axios client.
     */
    public successMiddleware = this.handleResponse.bind(this);

    /**
     * Error middleware for axios that checks if the errors were part of a long-runner.
     */
    public errorMiddleware = async (err: AxiosError | Error) => {
        if ("response" in err && err.response) {
            const newResponse = await this.handleResponse(err.response);
            if (newResponse !== err.response) {
                return Promise.resolve(newResponse);
            }
        }
        return Promise.reject(err);
    };

    /**
     * Take an existing response and see if we need to continue it some way.
     *
     * @param response The existing response.
     * @returns The final response. This may be the inital response or a subsequent one.
     */
    private async handleResponse(response: AxiosResponse, forceHandle: boolean = false): Promise<AxiosResponse> {
        // Make sure we don't handle responses that we originated by a LongRunnerClient.
        // Otherwise the wrong progress handlers might be used.
        // Particularly we want to make sure the default middleware doesn't interfere
        // with someone specifically calling creating their own LongRunnerClient.
        if (response.config[FROM_LONG_RUNNER] && !forceHandle) {
            return response;
        }

        // TODO: Add support for async responses.
        if (response.status >= 400 && response.data?.callbackPayload !== undefined) {
            const polledResponse = await this.pollCallbackPayload(response.data.callbackPayload);
            return polledResponse;
        } else {
            return response;
        }
    }

    /**
     * This function handles synchronous long polling.
     *
     * This function will call the `calls/run` endpoint for as long as callbackPayload is returned
     * from the endpoint. It effectively blocks the initial request from resolving and will only resolve
     * once all requests have been processed and return the final outcome.
     */
    private async pollCallbackPayload(initialCallbackPayload: string): Promise<AxiosResponse> {
        let callbackPayload: string | null = initialCallbackPayload;
        let response: AxiosResponse | null = null;
        do {
            response = await this.resumeCallbackPayload(callbackPayload);
            callbackPayload = response?.data?.callbackPayload ?? null;
        } while (callbackPayload !== null);
        return response;
    }

    /**
     * Given a callback payload, resume the call with the calls/run endpoint.
     *
     * Additionally, report and progress that was made.
     */
    private async resumeCallbackPayload(callbackPayload: string): Promise<AxiosResponse> {
        const callbackResponse = await this.apiClient
            .post("calls/run", callbackPayload, {
                headers: { "content-type": "application/system+jwt" },
                [FROM_LONG_RUNNER as any]: true,
            })
            .catch(this.extractErrResponse);

        // Report progress to any handlers.
        this.reportProgress(callbackResponse);

        // Return the full response.
        return callbackResponse;
    }

    /**
     * Extract an axios response from an axios error if it was intialized by the long-runner.
     *
     * We need to do this because just because we get an error code doesn't mean we should stop polling.
     *
     * @param err
     * @returns
     */
    private async extractErrResponse(err: AxiosError | Error) {
        if (
            "response" in err &&
            err.response &&
            err.response.config[FROM_LONG_RUNNER] &&
            err.response.data.callbackPayload !== undefined
        ) {
            return err.response;
        } else {
            return Promise.reject(err);
        }
    }

    /**
     * Report any progress to our handlers.
     *
     * @param response The response to pull data from.
     */
    private reportProgress(response: AxiosResponse) {
        if (response.data.progress) {
            const progress = response.data.progress as ILongRunnerProgress;
            if (progress.successIDs?.length > 0) {
                this.successHandler?.(progress.successIDs);
            }
            if (progress.failedIDs?.length > 0) {
                this.failHandler?.(progress.failedIDs, progress.exceptionsByID);
            }
        }
    }

    /**
     * Register a handler to know when some ids have been handled successfully.
     *
     * @param callback
     */
    public onSuccessIDs(callback: LongRunnerSuccessHandler) {
        this.successHandler = callback;
    }

    /**
     * Register a handler to know when some ids have failed.
     *
     * @param callback
     */
    public onFailedIDs(callback: LongRunnerFailedHandler) {
        this.failHandler = callback;
    }
}

///
/// Supporting Types
///

export enum LongRunnerMode {
    ASYNC = "async",
    SYNC = "sync",
}

type LongRunnerExceptions = Record<number, IError>;

interface ILongRunnerProgress {
    successIDs: number[];
    failedIDs: number[];
    exceptionsByID: LongRunnerExceptions;
    countTotalIDs: number | null;
}
export interface ILongRunnerResponse {
    progress: ILongRunnerProgress;
    callbackPayload: string | null;
}

interface IJobStatus {
    progress: ILongRunnerProgress | null;
    jobTrackingID: string;
}

export type LongRunnerSuccessHandler = (successIDs: number[]) => void;
export type LongRunnerFailedHandler = (errorsIDs: number[], exceptionsByID: LongRunnerExceptions) => void;

const FROM_LONG_RUNNER = "FROM_LONG_RUNNER";
