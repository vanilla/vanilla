/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, act, fireEvent, waitFor } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import DefaultCategoriesModal from "@dashboard/userPreferences/DefaultCategoriesModal";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { CategoryPostNotificationType } from "@vanilla/addon-vanilla/categories/categoriesTypes";

const queryClient = new QueryClient();

const dummmyData = [
    {
        categoryID: 1,
        name: "General",
        iconUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
        useEmailNotifications: false,
        postNotifications: CategoryPostNotificationType.FOLLOW,
    },
    {
        categoryID: 2,
        name: "Category Two",
        iconUrl: "https://us.v-cdn.net/5022541/uploads/userpics/446/n2RXLCE65F21T.jpg",
        useEmailNotifications: true,
        postNotifications: CategoryPostNotificationType.ALL,
    },
    {
        categoryID: 3,
        name: "Category Three",
        iconUrl: null,
        useEmailNotifications: true,
        postNotifications: CategoryPostNotificationType.DISCUSSIONS,
    },
];

const renderInProvider = () => {
    return render(
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["preferences.categoryFollowed.defaults"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    "preferences.categoryFollowed.defaults": JSON.stringify(dummmyData),
                                },
                            },
                        },
                    },
                }}
            >
                <DefaultCategoriesModal onCancel={() => null} />
            </TestReduxProvider>
        </QueryClientProvider>,
    );
};

describe("DefaultCategoriesModal", () => {
    it("Renders the categories with proper items checked", async () => {
        const { findByText, getAllByRole } = renderInProvider();
        expect(await findByText(/Add Categories to Follow by Default/)).toBeInTheDocument();

        await getAllByRole("row").forEach(async (row, rowIdx) => {
            if (rowIdx === 1) {
                const checkbox = row.querySelector(`input[type="checkbox"]`);
                expect(checkbox).toBeInTheDocument();
                expect(checkbox).not.toBeChecked();

                await act(async () => {
                    checkbox && fireEvent.click(checkbox);
                });

                waitFor(() => {
                    expect(checkbox).toBeChecked();
                    expect(screen.findByText(/Configuration changes saved./)).toBeInTheDocument();
                });
            } else if (rowIdx > 1) {
                const checkbox = row.querySelector(`input[type="checkbox"]`);
                expect(checkbox).toBeInTheDocument();
                expect(checkbox).toBeChecked();
            }
        });
    });

    it("Removes a category", async () => {
        const { findByRole, getAllByRole } = renderInProvider();
        waitFor(async () => {
            const removeButton = await findByRole("button", { name: "Remove Category" });
            expect(removeButton).toBeInTheDocument();

            await act(async () => {
                fireEvent.click(removeButton);
            });

            waitFor(async () => {
                const rows = await getAllByRole("row");
                expect(rows.length).toBe(3);
            });
        });
    });
});
