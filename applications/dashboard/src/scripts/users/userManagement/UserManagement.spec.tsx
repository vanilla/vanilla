/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { UserManagementContext } from "@dashboard/users/userManagement/UserManagementContext";
import { UserManagementImpl } from "@dashboard/users/userManagement/UserManagementPage";
import { render, waitFor, screen, act, within, fireEvent } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { useAddUser, useGetUsers, useUpdateUser } from "@dashboard/users/userManagement/UserManagement.hooks";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook } from "@testing-library/react-hooks";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
import { useLocalStorage } from "@vanilla/react-utils";
import { StackableTableColumnsConfig, StackableTableSortOption } from "@dashboard/tables/StackableTable/StackableTable";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { combineReducers, configureStore, createReducer } from "@reduxjs/toolkit";
import { Provider } from "react-redux";
import { IRoleState } from "@dashboard/roles/roleReducer";
import UserManagementFilter from "@dashboard/users/userManagement/UserManagementFilter";
import {
    mapFilterValuesToQueryParams,
    mapQueryParamsToFilterValues,
} from "@dashboard/users/userManagement/UserManagementUtils";
import UserManagementColumnsConfig from "@dashboard/users/userManagement/UserManagementColumnsConfig";
import { LiveAnnouncer } from "react-aria-live";
import { vitest } from "vitest";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";

describe("UserManagement", () => {
    const mockUser = {
        ...UserFixture.createMockUser({ userID: 4, name: "test-user" }),
        profileFields: { text: "test_text", tokens: ["Token 1", "Token 2"] },
    };
    const mockAddUser = UserFixture.createMockUser({ userID: 5, name: "new-test-user" });
    const mockUpdateUser = { ...mockAddUser, email: "test-updated@example.com" };

    mockUser.roles = [{ roleID: 1, name: "Member" }];

    const currentUserID = UserFixture.adminAsCurrent.data?.userID;
    const mockProfileFields = ProfileFieldsFixtures.mockProfileFields();

    const mockSiteTotalsCount = {
        counts: {
            user: {
                count: 33,
                isCalculating: false,
                isFiltered: false,
            },
        },
    };

    const mockRolesState: Partial<IRoleState> = {
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
                    name: "Administrator",
                    description: "Administrators have permission to do anything.",
                    type: "administrator",
                    deletable: true,
                    canSession: true,
                    personalInfo: false,
                },
            },
        },
    };

    const mockFilterValues = {
        roleIDs: [8],
        dateInserted: {
            start: "22.08.2023",
            end: "22.08.2024",
        },
        ipAddresses: "12.22.33.56",
        profileFields: {
            date: {
                start: "22.08.2023",
                end: "22.08.2024",
            },
        },
    };

    beforeEach(() => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users?expand=profileFields").reply(200, [mockUser], { "x-app-page-result-count": 1 });
        mockAdapter.onPost("/users").reply(201, [mockAddUser]);
        mockAdapter.onPatch(`/users/${mockUpdateUser.userID}`).reply(200, [mockUpdateUser]);
        mockAdapter.onGet("/site-totals").reply(200, mockSiteTotalsCount);
    });

    const mockColumnsConfig: StackableTableColumnsConfig = {
        "first column": {
            order: 1,
            wrapped: false,
            isHidden: false,
            sortDirection: StackableTableSortOption.DESC,
        },
        "second column fifth order": {
            order: 5,
            wrapped: false,
            isHidden: false,
        },
        "third column": {
            order: 3,
            wrapped: false,
            isHidden: true,
            sortDirection: StackableTableSortOption.DESC,
        },
        "user id": {
            order: 2,
            wrapped: false,
            isHidden: false,
            sortDirection: StackableTableSortOption.NO_SORT,
        },
        //profile fields
        "Text Field": {
            order: 4,
            wrapped: false,
            isHidden: false,
            columnID: mockProfileFields.filter((field) => field.label === "Text Field")[0].apiName,
        },
        "Tokens Field": {
            order: 6,
            wrapped: false,
            isHidden: false,
            columnID: mockProfileFields.filter((field) => field.label === "Tokens Field")[0].apiName,
        },
    };

    function queryClientWrapper() {
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });
        // eslint-disable-next-line react/display-name
        return ({ children }) => <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
    }

    function renderWithQueryClient(permissions?: any, currentUserID?: number, profileFields?: ProfileField[]) {
        render(
            <QueryClientProvider
                client={
                    new QueryClient({
                        defaultOptions: {
                            queries: {
                                retry: false,
                            },
                        },
                    })
                }
            >
                <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                    <UserManagementContext.Provider
                        value={{
                            profileFields: profileFields,
                            permissions: {
                                canAddUsers: true,
                                canEditUsers: true,
                                canDeleteUsers: true,
                                canViewPersonalInfo: true,
                                ...permissions,
                            },
                            RanksWrapperComponent: undefined,
                            currentUserID: currentUserID,
                            additionalFiltersSchemaFields: undefined,
                        }}
                    >
                        <MemoryRouter>
                            <UserManagementImpl />
                        </MemoryRouter>
                    </UserManagementContext.Provider>
                </CurrentUserContextProvider>
            </QueryClientProvider>,
        );
    }

    describe("UserManagementHooks", () => {
        it("useGetUsers() returns right data structure.", async () => {
            const { result, waitFor } = renderHook(() => useGetUsers({}), {
                wrapper: queryClientWrapper(),
            });

            await waitFor(() => result.current.isSuccess);

            expect(result.current.data?.users).toBeDefined();
            expect(result.current.data?.users.length).toBe(1);
            expect(result.current.data?.countUsers).toBe("1");
        });

        it("useAddUser() successfully adds a user.", async () => {
            const { result, waitFor } = renderHook(() => useAddUser(), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutate({
                    email: "test@example.com",
                    name: "new-test-user",
                    password: "a_b_c_12345",
                });
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data[0].userID).toBe(mockAddUser.userID);
        });

        it("useUpdateUser() successfully updates a user.", async () => {
            const { result, waitFor } = renderHook(() => useUpdateUser(mockUpdateUser.userID), {
                wrapper: queryClientWrapper(),
            });
            act(() => {
                result.current.mutate({
                    userID: 5,
                    email: "test-updated@example.com",
                });
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data[0].userID).toBe(mockUpdateUser.userID);
            expect(result.current.data[0].email).toBe(mockUpdateUser.email);
        });
    });

    it("UserManagementPage header with Add User button, searchbar, filter, columns configuration button and pager  are rendered correctly.", async () => {
        await act(async () => {
            renderWithQueryClient();
        });

        await waitFor(() => {
            expect(screen.getByText(/Manage Users/)).toBeInTheDocument();
            expect(
                screen.getByRole("button", {
                    name: "Add User",
                }),
            ).toBeInTheDocument();

            //we have the search button and the icon
            expect(
                screen.getAllByRole("button", {
                    name: "Search",
                }).length,
            ).toBe(2);

            //filter and columns configuration buttons are near search in the header
            const filterAndColumnsConfigContainer = screen.queryByTestId("filter-columnsConfig-container");
            expect(filterAndColumnsConfigContainer).toBeInTheDocument();
            expect(filterAndColumnsConfigContainer?.childElementCount).toBe(2);

            //pager is present
            expect(
                screen.getByRole("button", {
                    name: "Jump to a specific page",
                }),
            ).toBeInTheDocument();
        });
    });

    describe("UserManagementUtils", () => {
        it("Check if conversion from filter values to query params and vice versa is successful.", () => {
            const result = mapFilterValuesToQueryParams(mockFilterValues);

            expect(result).toBeDefined();
            expect(result.roleIDs).toBe(mockFilterValues.roleIDs);
            expect(typeof result.dateInserted).toBe("string");
            expect(Array.isArray(result.ipAddresses)).toBeTruthy();
            expect(result.ipAddresses?.[0]).toBe(mockFilterValues.ipAddresses);
            expect(typeof result.profileFields?.date).toBe("string");

            const convertBackResult = mapQueryParamsToFilterValues(result, mockProfileFields);

            expect(convertBackResult).toBeDefined();
            expect(convertBackResult.roleIDs).toBe(mockFilterValues.roleIDs);
            expect(typeof convertBackResult.dateInserted).toBe("object");
            expect(convertBackResult.dateInserted?.["start"]).toBe(mockFilterValues.dateInserted.start);
            expect(convertBackResult.dateInserted?.["end"]).toBe(mockFilterValues.dateInserted.end);
            expect(Array.isArray(convertBackResult.ipAddresses)).toBeTruthy();
            expect(convertBackResult.ipAddresses?.[0]).toBe(mockFilterValues.ipAddresses);
            expect(typeof convertBackResult.profileFields?.date).toBe("object");
            expect(convertBackResult.profileFields?.date["start"]).toBe(mockFilterValues.profileFields.date.start);
            expect(convertBackResult.profileFields?.date["end"]).toBe(mockFilterValues.profileFields.date.end);
        });
    });

    describe("UserManagementFilter", () => {
        it("Filter button click event opens the modal. The modal contains filter form fields.", async () => {
            const mockStore = configureStore({
                reducer: combineReducers({
                    roles: createReducer(mockRolesState, () => {}),
                }),
            });
            const result = render(
                <Provider store={mockStore}>
                    <UserManagementFilter updateQuery={() => {}} profileFields={mockProfileFields} />
                </Provider>,
            );
            const filterButton = result.getByRole("button");

            await act(async () => {
                fireEvent.click(filterButton);
            });
            // modal is rendered with inputs in it
            const modal = await result.findByRole("dialog");
            expect(modal).toBeInTheDocument();

            //check if input fields are there (some random fields)
            expect(modal.querySelector("form")).toBeInTheDocument();
            expect(within(modal).queryByText("First Visit")).toBeInTheDocument();
            expect(within(modal).queryByText("Roles")).toBeInTheDocument();
            expect(within(modal).queryByText("IP Address")).toBeInTheDocument();

            //and profile fields present as well
            expect(within(modal).queryByText(mockProfileFields[0].label)).toBeInTheDocument();
            expect(within(modal).queryByText(mockProfileFields[3].label)).toBeInTheDocument();
        });
    });

    describe("UserManagementConfig", () => {
        it("Config button click event opens the modal. If no columns selected, apply button won't save but show the error instead.", async () => {
            const mockFunction = vitest.fn();
            const result = render(
                <LiveAnnouncer>
                    <UserManagementColumnsConfig
                        onConfigurationChange={mockFunction}
                        treeColumns={[]}
                        additionalColumns={[]}
                        configuration={mockColumnsConfig}
                    />
                </LiveAnnouncer>,
            );
            const configButton = result.getByRole("button");

            await act(async () => {
                fireEvent.click(configButton);
            });

            // modal is rendered
            const modal = await result.findByRole("dialog");
            expect(modal).toBeInTheDocument();
            expect(modal.querySelector("form")).toBeInTheDocument();

            //as no columns selected to save, clicking on apply button won't close the modal and will show the error message
            const applyButton = within(modal).queryByText("Apply");
            expect(applyButton).toBeInTheDocument();

            await act(async () => {
                fireEvent.click(applyButton as HTMLElement);
            });

            const errorMessage = await within(modal).queryByText(
                "At least one visible column is required. Show or add a visible column.",
            );

            expect(errorMessage).toBeInTheDocument();
            expect(mockFunction).not.toHaveBeenCalled();
        });
    });

    describe("UserManagementTable", () => {
        it("User data is loaded and rendered in the table with action buttons.", async () => {
            await act(async () => {
                renderWithQueryClient();
            });

            await waitFor(() => {
                expect(
                    screen.getByText(`1 out of ${mockSiteTotalsCount.counts.user.count} users found.`),
                ).toBeInTheDocument();
                expect(screen.getByText(mockUser.name)).toBeInTheDocument();
                expect(screen.getByText(mockUser.roles[0].name)).toBeInTheDocument();

                //actions buttons are present as well
                const buttonsContainer = screen.queryByTestId("action-buttons-container");
                expect(buttonsContainer).toBeInTheDocument();
                expect(buttonsContainer?.childElementCount).toBe(2);

                // Spoof button
                expect(screen.queryByTitle("spoof")).not.toBeInTheDocument();
            });
        });
        it("Check user data in the table based on configuration, including main and additional columns and profile fields.", async () => {
            renderHook(() => useLocalStorage(`${currentUserID}_userManagement_columns_config`, mockColumnsConfig), {
                wrapper: queryClientWrapper(),
            });

            await act(async () => {
                renderWithQueryClient(undefined, currentUserID, mockProfileFields);
            });

            await waitFor(() => {
                //main column, username
                expect(screen.getByText(Object.keys(mockColumnsConfig)[0])).toBeInTheDocument();

                //additional column with matching value, user id, which is also sortable
                expect(screen.getByText(Object.keys(mockColumnsConfig)[3])).toBeInTheDocument();
                expect(screen.getByText(mockUser.userID.toString())).toBeInTheDocument();
                expect(screen.getByRole("button", { name: Object.keys(mockColumnsConfig)[3] })).toBeInTheDocument();

                //this one was hidden, should not show up
                expect(screen.queryByText(Object.keys(mockColumnsConfig)[2])).toBeNull();

                //profile fields both present and user profile field values  as well
                expect(screen.getByText(Object.keys(mockColumnsConfig)[4])).toBeInTheDocument();
                expect(screen.getByText(Object.keys(mockColumnsConfig)[5])).toBeInTheDocument();
                expect(screen.getByText(mockUser.profileFields.text)).toBeInTheDocument();
                expect(screen.getByText(mockUser.profileFields.tokens[0])).toBeInTheDocument();
                expect(screen.getByText(mockUser.profileFields.tokens[1])).toBeInTheDocument();
            });
        });

        it("User has a profile field chosen in columns configuration, but that profile field got disabled after.", async () => {
            renderHook(() => useLocalStorage(`${currentUserID}_userManagement_columns_config`, mockColumnsConfig), {
                wrapper: queryClientWrapper(),
            });

            // if one of the profile fields is disabled but user had it in configuration already, we should exclude it
            const newProfileFields = mockProfileFields.filter(
                (field) => field.label !== Object.keys(mockColumnsConfig)[5],
            );

            await act(async () => {
                renderWithQueryClient(undefined, currentUserID, newProfileFields);
            });

            await waitFor(() => {
                //profile field is not in table
                expect(screen.queryByText(Object.keys(mockColumnsConfig)[5])).toBeNull();
            });
        });

        it("User with spoof permission has spoof button", async () => {
            await act(async () => {
                renderWithQueryClient({ canSpoofUsers: true });
            });

            await waitFor(() => {
                const buttonsContainer = screen.queryByTestId("action-buttons-container");
                const spoofLink = buttonsContainer && within(buttonsContainer).getByTitle("spoof");
                expect(spoofLink).toBeInTheDocument();
            });
        });

        it("No add/edit/delete if no permissions for that.", async () => {
            await act(async () => {
                renderWithQueryClient({
                    canAddUsers: false,
                    canEditUsers: false,
                    canDeleteUsers: false,
                    canViewPersonalInfo: false,
                });
            });

            await waitFor(() => {
                expect(
                    screen.queryByRole("button", {
                        name: "Add User",
                    }),
                ).toBeNull();
                expect(screen.queryByTestId("action-buttons-container")?.childElementCount).toBe(0);
            });
        });
    });
});
