/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { LongRunnerClient } from "@library/LongRunnerClient";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter/types";
import { spy } from "sinon";

describe("LongRunnerClient", () => {
    let mockAdapter: MockAdapter;
    beforeEach(() => {
        mockAdapter = mockAPI();
    });

    it("chains requests sync requests.", async () => {
        const callback1 = "callback1";
        const callback2 = "callback2";
        const response3 = "hello3";
        mockAdapter
            .onPost("/bulk/thing")
            .replyOnce(408, { callbackPayload: callback1 })
            .onPost("/calls/run", callback1)
            .replyOnce(408, { callbackPayload: callback2 })
            .onPost("/calls/run", callback2)
            .replyOnce(200, response3);
        applyAnyFallbackError(mockAdapter);

        const response = await apiv2.post("/bulk/thing");
        expect(response.data).toEqual(response3);
    });

    it("it reports progress", async () => {
        const callback1 = "callback1";
        const callback2 = "callback2";
        const response3 = "hello3";
        mockAdapter
            .onPost("/bulk/thing")
            .replyOnce(408, {
                callbackPayload: callback1,
                progress: {
                    successIDs: [1, 2, 3],
                },
            })
            .onPost("/calls/run", callback1)
            .replyOnce(422, {
                callbackPayload: callback2,
                progress: {
                    successIDs: [1, 2, 3],
                    failedIDs: [6, 7],
                    exceptionsByID: {
                        6: {
                            message: "Not Found",
                        },
                        7: {
                            message: "No Permission",
                        },
                    },
                },
            })
            .onPost("/calls/run", callback2)
            .replyOnce(200, response3);
        applyAnyFallbackError(mockAdapter);

        const client = new LongRunnerClient(apiv2);
        const successSpy = spy();
        client.onSuccessIDs(successSpy);
        const failedSpy = spy();
        client.onFailedIDs(failedSpy);

        const response = await client.request({ method: "POST", url: "/bulk/thing" });
        // const response = err.response;
        expect(response.status).toBe(200);
        expect(successSpy.getCalls()).toHaveLength(2);
        expect(failedSpy.getCalls()).toHaveLength(1);
    });
});
