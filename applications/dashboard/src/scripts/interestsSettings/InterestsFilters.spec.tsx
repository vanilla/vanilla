/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen, act } from "@testing-library/react";
import { InterestsFilters } from "@dashboard/interestsSettings/InterestsFilters";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";

const MOCK_INTERESTS_FILTERS = {
    name: "Mock Interest",
    isDefault: true,
};

const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false, enabled: false } } });

function QueryWrapper(props: { children: React.ReactNode }) {
    return <QueryClientProvider client={queryClient}>{props.children}</QueryClientProvider>;
}

describe("InterestsFilters", () => {
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/profile-fields?enabled=true&formType=dropdown,tokens,checkbox").reply(200, []);
        mockAdapter.onGet("/tags?type=User&query=").reply(200, []);
        mockAdapter.onGet("/categories?outputFormat=flat&limit=50").reply(200, []);
    });

    it("Renders initial values", async () => {
        const mockUpdate = vitest.fn();
        act(() => {
            render(
                <QueryWrapper>
                    <InterestsFilters filters={MOCK_INTERESTS_FILTERS} updateFilters={mockUpdate} />
                </QueryWrapper>,
            );
        });
        await waitFor(() => {
            expect(screen.getByText("Interest Name")).toBeInTheDocument();
            expect(screen.getByRole("checkbox", { name: "Default Interests Only" })).toBeChecked();
        });
    });

    it("Removes default checked value", async () => {
        const mockUpdate = vitest.fn();
        act(() => {
            render(
                <QueryWrapper>
                    <InterestsFilters filters={MOCK_INTERESTS_FILTERS} updateFilters={mockUpdate} />
                </QueryWrapper>,
            );
        });
        const defaultCheckbox = screen.getByRole("checkbox", { name: "Default Interests Only" });
        const excludeCheckbox = screen.getByRole("checkbox", { name: "Exclude Default Interests" });
        await waitFor(() => {
            expect(defaultCheckbox).toBeChecked();
            expect(excludeCheckbox).not.toBeChecked();
        });
        fireEvent.click(excludeCheckbox);
        await waitFor(() => {
            expect(excludeCheckbox).toBeChecked();
            expect(defaultCheckbox).not.toBeChecked();
            expect(mockUpdate).toHaveBeenCalledWith({ isDefault: false, name: "Mock Interest" });
        });
    });
});
