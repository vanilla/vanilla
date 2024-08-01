/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act, cleanup, waitFor } from "@testing-library/react";
import { MembersSearchFilterPanel } from "@dashboard/components/panels/MembersSearchFilterPanel";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { IRoleState } from "@dashboard/roles/roleReducer";
import { LoadStatus } from "@library/@types/api/core";
import { createReducer } from "@reduxjs/toolkit";
import MEMBERS_SEARCH_DOMAIN from "@dashboard/components/panels/MembersSearchDomain";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";
import { SearchService } from "@library/search/SearchService";
import { MemoryRouter } from "react-router";

export const mockRolesState: Partial<IRoleState> = {
    rolesByID: {
        status: LoadStatus.SUCCESS,
        data: {
            1: {
                roleID: 1,
                name: "Guest",
                description: `Guests can only view content. Anyone browsing the site who is not signed in is considered to be a "Guest".`,
                type: "guest",
                deletable: false,
                canSession: false,
                personalInfo: false,
            },
            2: {
                roleID: 2,
                name: "Member",
                description: "Members can participate in discussions.",
                type: "member",
                deletable: true,
                canSession: true,
                personalInfo: false,
            },

            3: {
                roleID: 3,
                name: "Administator",
                description: "Administrators have permission to do anything.",
                type: "administrator",
                deletable: true,
                canSession: true,
                personalInfo: false,
            },
        },
    },
};

beforeAll(async () => {
    COMMUNITY_SEARCH_SOURCE.addDomain(MEMBERS_SEARCH_DOMAIN);
    await COMMUNITY_SEARCH_SOURCE.loadDomains();
    SearchService.addSource(COMMUNITY_SEARCH_SOURCE);
});

describe("MembersSearchFilterPanel", () => {
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        extraReducers: {
            roles: createReducer(mockRolesState, () => {}),
        },
    });

    const MockSearchFilterPanel = () => {
        return (
            <MemoryRouter>
                <SearchFormContextProvider>
                    <MockProfileFieldsProvider>
                        <MembersSearchFilterPanel />
                    </MockProfileFieldsProvider>
                </SearchFormContextProvider>
            </MemoryRouter>
        );
    };

    afterAll(() => {
        cleanup();
    });

    it("Render filter form without email field because user does not have permission.", async () => {
        await act(async () => {
            render(
                <PermissionsFixtures.SpecificPermissions permissions={["profiles.view"]}>
                    <MockSearchFilterPanel />
                </PermissionsFixtures.SpecificPermissions>,
            );
        });

        await waitFor(() => {
            expect(screen.queryByText("Filter Results")).toBeInTheDocument();
        });

        // Username input
        expect(screen.queryByRole("textbox", { name: "Username" })).toBeInTheDocument();
        // Email input should not be visible
        expect(screen.queryByRole("textbox", { name: "Email" })).not.toBeInTheDocument();

        // Registered date range has 2 date inputs
        const dateInputs = await screen.findAllByRole("date");
        expect(dateInputs).toHaveLength(2);

        // Filter button
        expect(screen.getByRole("button", { name: "Filter" })).toBeInTheDocument();
        // Clear button
        expect(screen.getByRole("button", { name: "Clear All" })).toBeInTheDocument();

        // Role multi-select
        expect(screen.getByRole("textbox", { name: "Search" })).toBeInTheDocument();
    });

    it("Render filter form with email field because user has permission.", async () => {
        await act(async () => {
            render(
                <PermissionsFixtures.SpecificPermissions permissions={["personalInfo.view", "profiles.view"]}>
                    <MockSearchFilterPanel />
                </PermissionsFixtures.SpecificPermissions>,
            );
        });

        // Username input
        expect(screen.getByRole("textbox", { name: "Username" })).toBeInTheDocument();
        // Email input should be visible
        expect(screen.queryByRole("textbox", { name: "Email" })).toBeInTheDocument();

        // Registered date range has 2 date inputs
        const dateInputs = await screen.findAllByRole("date");
        expect(dateInputs).toHaveLength(2);

        // Filter button
        expect(screen.getByRole("button", { name: "Filter" })).toBeInTheDocument();
        // Clear button
        expect(screen.getByRole("button", { name: "Clear All" })).toBeInTheDocument();

        // Role multi-select
        expect(screen.getByRole("textbox", { name: "Search" })).toBeInTheDocument();
    });

    it("Clear filter values", async () => {
        await act(async () => {
            render(<MockSearchFilterPanel />);
        });

        const usernameField = screen.getByRole("textbox", { name: "Username" });
        await act(async () => {
            fireEvent.change(usernameField, { target: { value: "testy" } });
        });

        expect(usernameField).toHaveValue("testy");

        fireEvent.click(screen.getByRole("button", { name: "Clear All" }));

        await waitFor(() => {
            expect(usernameField).toHaveValue("");
        });
    });
});
