/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RenderResult, render, fireEvent, act } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";

import DigestOptInExistingUsers from "@dashboard/emailSettings/components/DigestOptInExistingUsers";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});
describe("DigestOptInExistingUsers", () => {
    let result: RenderResult;
    let mockAdapter: MockAdapter;

    beforeEach(async () => {
        mockAdapter = mockAPI();
        mockAdapter.onPatch("/config").reply(201, {});
        mockAdapter.onPost("/digest/backfill-optin").reply(201, {});

        result = render(
            <QueryClientProvider client={queryClient}>
                <DigestOptInExistingUsers />
            </QueryClientProvider>,
        );
        await vi.dynamicImportSettled();
    });

    afterEach(() => {
        mockAdapter.reset();
        vitest.clearAllMocks();
    });

    it("should render the backdate button but not the settings modal", async () => {
        const backDateButton = await result.findByRole("button", { name: "Backdate" });
        const modal = result.queryByRole("dialog");

        expect(backDateButton).toBeInTheDocument();
        expect(modal).not.toBeInTheDocument();
    });

    it("should open the modal when the user clicks the button", async () => {
        const { getByRole } = result;
        const backDateButton = await result.findByRole("button", { name: "Backdate" });

        fireEvent.click(backDateButton);

        const modalTitle = "Opt-in Existing Users to Digest";

        expect(result.getByText(modalTitle)).toBeInTheDocument();
        expect(result.getByRole("link", { name: "More information" })).toBeInTheDocument();
        expect(
            result
                .getByRole("link", { name: "More information" })
                .getAttribute("href")
                ?.includes("https://www.higherlogic.com/gdpr/"),
        ).toBeTruthy();

        const submitButton = getByRole("button", { name: "Run" });
        expect(submitButton).toBeInTheDocument();
    });

    it("should POST to the API when the user clicks the Run button", async () => {
        const backDateButton = await result.findByRole("button", { name: "Backdate" });
        fireEvent.click(backDateButton);

        const runButton = await result.findByRole("button", { name: "Run" });
        const form = runButton.closest("form") as HTMLFormElement;

        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockAdapter.history.post.length).toBe(1);
    });
});
