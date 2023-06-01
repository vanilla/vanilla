/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { UserManagementContext } from "@dashboard/users/userManagement/UserManagementContext";
import { UserManagementImpl } from "@dashboard/users/userManagement/UserManagementPage";
import { render, waitFor, screen, act } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { useAddUser, useGetUsers, useUpdateUser } from "./UserManagement.hooks";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook } from "@testing-library/react-hooks";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";

jest.setTimeout(20000);
describe("UserManagement", () => {
    const mockUser = UserFixture.createMockUser({ userID: 2, name: "test-user" });
    const mockAddUser = UserFixture.createMockUser({ userID: 5, name: "new-test-user" });
    const mockUpdateUser = { ...mockAddUser, email: "test-updated@example.com" };

    mockUser.roles = [{ roleID: 1, name: "Member" }];

    const mockAdapter = mockAPI();
    mockAdapter.onGet("/users?expand=profileFields").reply(200, [mockUser], { "x-app-page-result-count": 1 });
    mockAdapter.onPost("/users").reply(201, [mockAddUser]);
    mockAdapter.onPatch(`/users/${mockUpdateUser.userID}`).reply(200, [mockUpdateUser]);

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

    function renderWithQueryClient(permissions?: any) {
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
                            profileFields: undefined,
                            permissions: {
                                canAddUsers: true,
                                canEditUsers: true,
                                canDeleteUsers: true,
                                canViewPersonalInfo: true,
                                ...permissions,
                            },
                            RanksWrapperComponent: undefined,
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
