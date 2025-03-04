/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { render, screen } from "@testing-library/react";
import { ApplyLayout } from "@dashboard/appearance/components/ApplyLayout";
import { ILayoutDetails, LayoutRecordType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const mockLayout = {
    layoutID: 1,
    layoutViewType: "home",
    name: "My test layout",
    insertUserID: 2,
    layoutViews: [
        {
            layoutID: 1,
            layoutViewID: 11,
            layoutViewType: "testViewType",
            recordID: 111,
            insertUserID: 2,
            updateUserID: 2,
            dateInserted: "2021-10-01",
            recordType: LayoutRecordType.GLOBAL,
            record: { name: "My test record", url: "https://#" },
        },
    ],
};

function renderInProvider() {
    render(
        <TestReduxProvider
            state={{
                config: {
                    configsByLookupKey: {
                        [stableObjectHash(["customLayout.home"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                    },
                },
            }}
        >
            <QueryClientProvider client={queryClient}>
                <ApplyLayout layout={mockLayout as ILayoutDetails} forceModalOpen />
            </QueryClientProvider>
        </TestReduxProvider>,
    );
}
describe("ApplyLayout", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/config").reply(200, {});
    });

    it("Registered external apply options are included in modal", async () => {
        ApplyLayout.registerExternalApplyOptionGenerator((viewType) => {
            return {
                key: "myTestApplyOptionKey",
                recordType: LayoutRecordType.GLOBAL,
                applyOptionLabel: `My test label for apply option - ${viewType}`,
                schema: {},
            };
        });
        renderInProvider();
        expect(await screen.getByRole("dialog")).toBeInTheDocument();
        expect(
            await screen.getByText(`My test label for apply option - ${mockLayout.layoutViewType}`),
        ).toBeInTheDocument();
    });
});
