/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { ReportReasonList } from "@dashboard/communityManagementSettings/ReportReasonList";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

describe("ReportReasonList", async () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/report-reasons").reply(
            200,
            [1, 2, 3, 4].map((x) => {
                return CommunityManagementFixture.getReason({
                    reportReasonID: `reason-${x}`,
                    name: `Mock Reason Name ${x}`,
                    description: `Mock Reason Description ${x}`,
                });
            }),
        );
    });
    it("Renders Table", async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <ReportReasonList />
            </QueryClientProvider>,
        );
        await waitFor(() => {
            expect(screen.getByText("Mock Reason Name 1")).toBeInTheDocument();
            expect(screen.getByText("Mock Reason Name 2")).toBeInTheDocument();
            expect(screen.getByText("Mock Reason Name 3")).toBeInTheDocument();
            expect(screen.getByText("Mock Reason Name 4")).toBeInTheDocument();
        });
    });
    it.skip("Opens Edit Modal", async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <ReportReasonList />
            </QueryClientProvider>,
        );
        await waitFor(() => {
            expect(screen.getByText("Mock Reason Name 1")).toBeInTheDocument();
        });

        await waitFor(() => {
            const editButton = screen.getAllByRole("button", { name: "Edit" })[0];
            fireEvent.click(editButton);
        });

        await waitFor(() => {
            expect(screen.getByText("Edit Report Reason")).toBeInTheDocument();
        });
    });
    it("Opens Delete Confirmation Modal", async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <ReportReasonList />
            </QueryClientProvider>,
        );
        await waitFor(() => {
            expect(screen.getByText("Mock Reason Name 1")).toBeInTheDocument();
        });
        fireEvent.click(screen.getAllByLabelText("Delete")[0]);
        await waitFor(() => {
            expect(screen.getByText("Are you sure you want to delete this reason?")).toBeInTheDocument();
        });
    });
    it.skip("Opens Add Reason Modal", async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <ReportReasonList />
            </QueryClientProvider>,
        );
        fireEvent.click(screen.getAllByText("Add Reason")[0]);
        await waitFor(() => {
            expect(screen.getByText("Add Report Reason")).toBeInTheDocument();
        });
    });
});
