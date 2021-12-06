/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import {
    ILongRunnerResponse,
    LongRunnerClient,
    LongRunnerFailedHandler,
    LongRunnerSuccessHandler,
} from "@library/LongRunnerClient";
import { AsyncState, useAsyncFn } from "@vanilla/react-utils";
import { useEffect, useMemo } from "react";

export function useLongRunnerAction<RequestBody = any>(
    method: "POST" | "PATCH" | "DELETE" | "PUT",
    url: string,
    handlers: { success?: LongRunnerSuccessHandler; failed?: LongRunnerFailedHandler } = {},
): [AsyncState<ILongRunnerResponse>, (body: RequestBody) => Promise<ILongRunnerResponse>] {
    const longRunnerClient = useMemo(() => {
        return new LongRunnerClient(apiv2);
    }, []);

    useEffect(() => {
        if (handlers.success) {
            longRunnerClient.onSuccessIDs(handlers.success);
        }
    }, [handlers.success]);

    useEffect(() => {
        if (handlers.failed) {
            longRunnerClient.onFailedIDs(handlers.failed);
        }
    }, [handlers.failed]);

    const [state, request] = useAsyncFn(
        async (body: RequestBody) => {
            const response = await longRunnerClient.request({
                method,
                url,
                data: body,
            });
            return response.data;
        },
        [longRunnerClient, method, url],
    );

    return [state, request];
}
