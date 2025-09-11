/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EscalationFilters, IEscalationFilters } from "./EscalationFilters";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import MockAdapter from "axios-mock-adapter";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import React from "react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { mockAPI } from "@library/__tests__/utility";
import { setMeta } from "@library/utility/appUtils";

describe("EscalationFilters", () => {
    const mockProps = {
        value: {
            statuses: [],
            reportReasonID: [],
            assignedUserID: [],
            recordUserID: [],
            recordUserRoleID: [],
        } as IEscalationFilters,
        onFilter: vi.fn(),
    };

    let queryClient: QueryClient;
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                    staleTime: Infinity,
                },
            },
        });
        mockAdapter = mockAPI();

        // Mock the report reasons API for ReasonFilter component
        mockAdapter.onGet("/report-reasons").reply(200, [
            { reportReasonID: "reason1", name: "Spam", description: "Spam content" },
            { reportReasonID: "reason2", name: "Inappropriate", description: "Inappropriate content" },
        ]);

        vi.clearAllMocks();
    });

    const renderComponent = (props = mockProps, permissionsWrapper = PermissionsFixtures.NoPermissions) => {
        return render(
            <TestReduxProvider>
                <QueryClientProvider client={queryClient}>
                    <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                        {React.createElement(permissionsWrapper, {}, <EscalationFilters {...props} />)}
                    </CurrentUserContextProvider>
                </QueryClientProvider>
            </TestReduxProvider>,
        );
    };

    it("displays member filters when restrictMemberFilterUI config is false", () => {
        setMeta("moderation.restrictMemberFilterUI", false);
        renderComponent(mockProps, PermissionsFixtures.NoPermissions);

        expect(screen.getByText("Assignee")).toBeInTheDocument();
        expect(screen.getByText("Post Author")).toBeInTheDocument();
        expect(screen.getByText("Post Author Role")).toBeInTheDocument();
    });

    it("displays member filters when restrictMemberFilterUI config is undefined", () => {
        renderComponent(mockProps, PermissionsFixtures.NoPermissions);

        expect(screen.getByText("Assignee")).toBeInTheDocument();
        expect(screen.getByText("Post Author")).toBeInTheDocument();
        expect(screen.getByText("Post Author Role")).toBeInTheDocument();
    });

    it("displays restricted assignee options when restrictMemberFilterUI is true and user has no permissions", () => {
        setMeta("moderation.restrictMemberFilterUI", true);
        renderComponent(mockProps, PermissionsFixtures.NoPermissions);

        // Should show restricted assignee filter
        expect(screen.getByText("Assignee")).toBeInTheDocument();

        // Should not show other member filters
        expect(screen.queryByText("Post Author")).not.toBeInTheDocument();
        expect(screen.queryByText("Post Author Role")).not.toBeInTheDocument();
    });

    it("displays member filters when restrictMemberFilterUI is true and user has community.moderate permission", () => {
        setMeta("moderation.restrictMemberFilterUI", true);
        const CommunityModeratePermissions = (props: React.PropsWithChildren<{}>) => (
            <PermissionsFixtures.SpecificPermissions permissions={["community.moderate"]}>
                {props.children}
            </PermissionsFixtures.SpecificPermissions>
        );

        renderComponent(mockProps, CommunityModeratePermissions);

        expect(screen.getByText("Assignee")).toBeInTheDocument();
        expect(screen.getByText("Post Author")).toBeInTheDocument();
        expect(screen.getByText("Post Author Role")).toBeInTheDocument();
    });

    it("displays member filters when restrictMemberFilterUI is true and user has site.manage permission", () => {
        setMeta("moderation.restrictMemberFilterUI", true);
        const SiteManagePermissions = (props: React.PropsWithChildren<{}>) => (
            <PermissionsFixtures.SpecificPermissions permissions={["site.manage"]}>
                {props.children}
            </PermissionsFixtures.SpecificPermissions>
        );

        renderComponent(mockProps, SiteManagePermissions);

        expect(screen.getByText("Assignee")).toBeInTheDocument();
        expect(screen.getByText("Post Author")).toBeInTheDocument();
        expect(screen.getByText("Post Author Role")).toBeInTheDocument();
    });

    it("displays member filters when restrictMemberFilterUI is true and user has both permissions", () => {
        setMeta("moderation.restrictMemberFilterUI", true);
        const BothPermissions = (props: React.PropsWithChildren<{}>) => (
            <PermissionsFixtures.SpecificPermissions permissions={["community.moderate", "site.manage"]}>
                {props.children}
            </PermissionsFixtures.SpecificPermissions>
        );

        renderComponent(mockProps, BothPermissions);

        expect(screen.getByText("Assignee")).toBeInTheDocument();
        expect(screen.getByText("Post Author")).toBeInTheDocument();
        expect(screen.getByText("Post Author Role")).toBeInTheDocument();
    });

    it("always displays Status and Reason filters regardless of config", async () => {
        renderComponent(mockProps, PermissionsFixtures.NoPermissions);

        expect(screen.getByText("Status")).toBeInTheDocument();
        await waitFor(() => {
            expect(screen.getByText("Reason")).toBeInTheDocument();
        });
    });
});
