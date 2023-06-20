/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { UserManagementContext } from "@dashboard/users/userManagement/UserManagementContext";
import { UserManagementImpl } from "@dashboard/users/userManagement/UserManagementPage";
import { render, waitFor, screen, act, within } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { useAddUser, useGetUsers, useUpdateUser } from "@dashboard/users/userManagement/UserManagement.hooks";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook } from "@testing-library/react-hooks";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { useSessionStorage } from "@vanilla/react-utils";
import { StackableTableColumnsConfig, StackableTableSortOption } from "@dashboard/tables/StackableTable/StackableTable";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";

jest.setTimeout(20000);
describe("UserManagement", () => {
    const mockUser = {
        ...UserFixture.createMockUser({ userID: 4, name: "test-user" }),
        profileFields: { text: "test_text", dropdown: ["Option1", "Option2"] },
    };
    const mockAddUser = UserFixture.createMockUser({ userID: 5, name: "new-test-user" });
    const mockUpdateUser = { ...mockAddUser, email: "test-updated@example.com" };

    mockUser.roles = [{ roleID: 1, name: "Member" }];

    const currentUserID = UserFixture.adminAsCurrent.data?.userID;
    const mockProfileFields = ProfileFieldsFixtures.mockProfileFields();

    const mockAdapter = mockAPI();
    mockAdapter.onGet("/users?expand=profileFields").reply(200, [mockUser], { "x-app-page-result-count": 1 });
    mockAdapter.onPost("/users").reply(201, [mockAddUser]);
    mockAdapter.onPatch(`/users/${mockUpdateUser.userID}`).reply(200, [mockUpdateUser]);

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
        "Dropdown Field": {
            order: 6,
            wrapped: false,
            isHidden: false,
            columnID: mockProfileFields.filter((field) => field.label === "Dropdown Field")[0].apiName,
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
                <TestReduxProvider
                    state={{
                        users: {
                            current: {
                                ...UserFixture.adminAsCurrent,
                            },
                            usersByID: {},
                            patchStatusByPatchID: {
                                "2-userPatch-0": {
                                    status: LoadStatus.LOADING,
                                },
                            },
                        },
                    }}
                >
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
                        }}
                    >
                        <MemoryRouter>
                            <UserManagementImpl />
                        </MemoryRouter>
                    </UserManagementContext.Provider>
                </TestReduxProvider>
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
            expect(result.current.data?.countUsers).toBe(1);
        });

        it("useAddUser() successfully adds a user.", async () => {
            const { result, waitFor } = renderHook(() => useAddUser(), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync({
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
                result.current.mutateAsync({
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

    it("UserManagementPage header with Add User button, searchbar, pager  are rendered correctly.", async () => {
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

            //pager is present
            expect(
                screen.getByRole("button", {
                    name: "Jump to a specific page",
                }),
            ).toBeInTheDocument();
        });
    });

    describe("UserManagementTable", () => {
        it("User data is loaded and rendered in the table with action buttons.", async () => {
            await act(async () => {
                renderWithQueryClient();
            });

            await waitFor(() => {
                expect(screen.getByText("1 user found.")).toBeInTheDocument();
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
            renderHook(() => useSessionStorage(`${currentUserID}_userManagement_columns_config`, mockColumnsConfig), {
                wrapper: queryClientWrapper(),
            });

            await act(async () => {
                renderWithQueryClient(undefined, currentUserID, mockProfileFields);
            });

            await waitFor(() => {
                //main column, username
                expect(screen.getByText(Object.keys(mockColumnsConfig)[0])).toBeInTheDocument();

                //additional column with matching value, user id
                expect(screen.getByText(Object.keys(mockColumnsConfig)[3])).toBeInTheDocument();
                expect(screen.getByText(mockUser.userID.toString())).toBeInTheDocument();

                //this one was hidden, should not show up
                expect(screen.queryByText(Object.keys(mockColumnsConfig)[2])).toBeNull();

                //profile fields both present and user profile field values  as well
                expect(screen.getByText(Object.keys(mockColumnsConfig)[4])).toBeInTheDocument();
                expect(screen.getByText(Object.keys(mockColumnsConfig)[5])).toBeInTheDocument();
                expect(screen.getByText(mockUser.profileFields.text)).toBeInTheDocument();
                expect(screen.getByText(mockUser.profileFields.dropdown[0])).toBeInTheDocument();
                expect(screen.getByText(mockUser.profileFields.dropdown[1])).toBeInTheDocument();
            });
        });

        it("User has a profile field chosen in columns configuration, but that profile field got disabled after.", async () => {
            renderHook(() => useSessionStorage(`${currentUserID}_userManagement_columns_config`, mockColumnsConfig), {
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
