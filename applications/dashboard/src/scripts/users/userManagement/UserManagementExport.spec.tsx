/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import { useUsersExport } from "@dashboard/users/userManagement/UserManagementExport";
import { UserManagementColumnNames } from "@dashboard/users/userManagement/UserManagementUtils";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { mockAPI } from "@library/__tests__/utility";
import { render, RenderResult } from "@testing-library/react";
import { downloadAsFile } from "@vanilla/dom-utils";
import userEvent from "@testing-library/user-event";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";

let mockApi: MockAdapter;
// Mock our time.
Date.now = vitest.fn(() => new Date("2022-12-01T00:05:30Z").valueOf());
const expectedFileName = "user-export_2022-12-01_00-05-30";

describe("useUsersExport()", () => {
    let downloader = vitest.fn();
    let rendered: RenderResult;

    beforeEach(() => {
        mockApi = mockAPI({ onNoMatch: "throwException" });
        downloader.mockReset();
        rendered = render(
            <ToastProvider>
                <MiniUserExport downloader={downloader} query={{}} />
            </ToastProvider>,
        );
    });

    async function startExport() {
        await userEvent.click(rendered.getByText("Export"));
    }

    it("can fetch a single set of users", async () => {
        mockApi.onGet("/users.csv").replyOnce(200, "csvdata");

        await startExport();

        // User is notified of completion.
        rendered.getByText("User Export Complete");
        expect(downloader).toHaveBeenCalledTimes(1);
        expect(downloader).toHaveBeenCalledWith("csvdata", expectedFileName);
    });

    it("can paginate through users", async () => {
        mockApi
            .onGet("/users.csv")
            .replyOnce(200, "header1,header2\nval1.1,val1.2\n", {
                "x-app-page-next-url": "/users.csv?page=2&cursor=servercursor",
            })
            .onGet("/users.csv?page=2&cursor=servercursor")
            .replyOnce(200, "header1,header2\nval2.1,val2.2\n");

        await startExport();

        // User is notifified of completion.
        rendered.getByText("User Export Complete");

        expect(downloader).toHaveBeenCalledTimes(1);
        const expectedCsv = `header1,header2\nval1.1,val1.2\nval2.1,val2.2\n`;
        expect(downloader).toHaveBeenCalledWith(expectedCsv, expectedFileName);
    });
});

function MiniUserExport(props: { downloader: typeof downloadAsFile; query: IGetUsersQueryParams }) {
    const userExport = useUsersExport(
        [UserManagementColumnNames.USER_NAME, UserManagementColumnNames.USER_ID],
        props.downloader,
    );

    return (
        <div>
            <button
                type="submit"
                onClick={() => {
                    void userExport.exportUsers(props.query);
                }}
            >
                Export
            </button>
            {userExport.cancelDialogue}
        </div>
    );
}
