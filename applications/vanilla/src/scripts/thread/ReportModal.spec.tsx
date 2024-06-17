/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import ReportModal from "./ReportModal.loadable";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { ComponentProps } from "react";
import MockAdapter from "axios-mock-adapter";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: true,
            retry: false,
            staleTime: Infinity,
        },
    },
});

async function renderInProvider(props?: Partial<ComponentProps<typeof ReportModal>>) {
    render(
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <QueryClientProvider client={queryClient}>
                    <ReportModal
                        discussionName={"Mock discussion name"}
                        recordID={"1"}
                        recordType={"discussion"}
                        isVisible={true}
                        onVisibilityChange={() => null}
                        {...props}
                    />
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>,
    );
}

describe("ReportModal", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/reports/reasons").reply(200, [
            {
                reportReasonID: "reason-1",
                name: "Mock Reason Name 1",
                description: "Mock Reason Description 1",
                sort: 1,
            },
            {
                reportReasonID: "reason-2",
                name: "Mock Reason Name 2",
                description: "Mock Reason Description 2",
                sort: 1,
            },
        ]);
        mockAdapter.onPost("/reports").reply(200, {});
        mockAdapter.onPost("/escalations").reply(200, {});
    });

    it("Renders dynamic reasons", async () => {
        expect.assertions(2);
        await renderInProvider();
        await waitFor(() => expect(screen.getByText("Mock Reason Name 1")).toBeInTheDocument());
        await waitFor(() => expect(screen.getByText("Mock Reason Name 2")).toBeInTheDocument());
    });

    it("Posts report", async () => {
        await renderInProvider();
        await waitFor(() => screen.getByText("Mock Reason Name 1"));
        fireEvent.click(screen.getByText("Mock Reason Name 1"));
        fireEvent.click(screen.getByRole("button", { name: "Send Report" }));
        await waitFor(() => {
            expect(mockAdapter.history.post.length).toBe(1);
        });
        expect(mockAdapter.history.post[0].url).toBe("/reports");
    });

    it("Posts escalation", async () => {
        render(
            <TestReduxProvider>
                <PermissionsFixtures.AllPermissions>
                    <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                        <QueryClientProvider client={queryClient}>
                            <ReportModal
                                discussionName={"Mock discussion name"}
                                recordID={"1"}
                                recordType={"discussion"}
                                isVisible={true}
                                onVisibilityChange={() => null}
                            />
                        </QueryClientProvider>
                    </CurrentUserContextProvider>
                </PermissionsFixtures.AllPermissions>
            </TestReduxProvider>,
        );
        await waitFor(() => screen.getByText("Mock Reason Name 1"));
        fireEvent.click(screen.getByText("Mock Reason Name 1"));
        fireEvent.click(screen.getByText("Escalate immediately"));
        fireEvent.click(screen.getByRole("button", { name: "Create Escalation" }));
        await waitFor(() => {
            expect(mockAdapter.history.post.length).toBe(2);
            expect(mockAdapter.history.post[1].url).toBe("/escalations");
        });
    });
});
