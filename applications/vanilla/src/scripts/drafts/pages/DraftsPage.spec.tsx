/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen } from "@testing-library/react";
import MockAdapter from "axios-mock-adapter";
import DraftsPage from "@vanilla/addon-vanilla/drafts/pages/DraftsPage";
import { MemoryRouter } from "react-router";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const renderInProvider = async (permissions?: string[]) => {
    const content = (
        <QueryClientProvider client={queryClient}>
            <MemoryRouter>
                <DraftsPage />
            </MemoryRouter>
        </QueryClientProvider>
    );
    render(
        permissions ? (
            <PermissionsFixtures.SpecificPermissions permissions={permissions}>
                {content}
            </PermissionsFixtures.SpecificPermissions>
        ) : (
            content
        ),
    );
    await vi.dynamicImportSettled();
};

describe("DraftsPage", () => {
    setMeta("featureFlags.DraftScheduling.Enabled", true);
    const mockDrafts = [
        {
            draftID: 8,
            recordType: "discussion",
            attributes: {
                body: '[{"type":"p","children":[{"text":"hey test"}]}]',
                format: "rich2",
                draftType: "discussion",
                draftMeta: {
                    tagIDs: [],
                    newTagNames: [],
                    pinLocation: "none",
                    postTypeID: "discussion",
                },
                lastSaved: "2025-02-25T19:15:41.254Z",
            },
            insertUserID: 21,
            dateInserted: "2025-02-25T19:15:41+00:00",
            updateUserID: 21,
            dateUpdated: "2025-02-25T19:15:47+00:00",
            name: "howdy",
            excerpt: "hey test",
            editUrl: "https://dev.vanilla.local/post/editdiscussion/0/8",
            breadCrumbs: null,
            permaLink: null,
            dateScheduled: null,
            draftStatus: "draft",
            failedReason: null,
        },
    ];
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(/drafts/).reply(200, mockDrafts);
    });

    afterEach(() => {
        mockAdapter.reset();
        vitest.clearAllMocks();
    });

    it("User does not have schedule.allow permission, render drafts only with no tabs", async () => {
        await renderInProvider();
        await vi.dynamicImportSettled();

        const pageTitleOnlyDrafts = await screen.getByText("Manage Drafts");
        const pageTitleWithSchedule = await screen.queryByText("Manage Drafts and Scheduled Content");
        const tabButtons = await screen.queryAllByRole("tab");
        const draftItemExcerpt = await screen.getByText(mockDrafts[0].excerpt);
        const draftItemName = await screen.getByText(mockDrafts[0].name);

        expect(pageTitleOnlyDrafts).toBeInTheDocument();
        expect(pageTitleWithSchedule).not.toBeInTheDocument();
        expect(tabButtons).toHaveLength(0);
        expect(draftItemName).toBeInTheDocument();
        expect(draftItemExcerpt).toBeInTheDocument();
    });

    it("User has schedule.allow permission, render drafts and schedules, with tabs", async () => {
        await renderInProvider(["schedule.allow"]);
        await vi.dynamicImportSettled();

        const pageTitleOnlyDrafts = await screen.queryByText("Manage Drafts");
        const pageTitleWithSchedule = await screen.getByText("Manage Drafts and Scheduled Content");
        const tabButtons = await screen.queryAllByRole("tab");

        expect(pageTitleOnlyDrafts).not.toBeInTheDocument();
        expect(pageTitleWithSchedule).toBeInTheDocument();
        expect(tabButtons).toHaveLength(3);
    });
});
