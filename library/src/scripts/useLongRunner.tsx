/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { LongRunnerClient, LongRunnerFailedHandler, LongRunnerSuccessHandler } from "@library/LongRunnerClient";
import { useMutation } from "@tanstack/react-query";
import { useEffect, useMemo } from "react";

export function useLongRunnerAction<RequestBody = any>(
    method: "POST" | "PATCH" | "DELETE" | "PUT",
    url: string,
    handlers: { success?: LongRunnerSuccessHandler; failed?: LongRunnerFailedHandler } = {},
) {
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

    const mutation = useMutation<any, IError, RequestBody>({
        mutationFn: async (body: RequestBody) => {
            const response = await longRunnerClient.request({
                method,
                url,
                data: body,
            });
            return response.data;
        },
        mutationKey: [longRunnerClient, method, url],
    });

    return mutation;
}
