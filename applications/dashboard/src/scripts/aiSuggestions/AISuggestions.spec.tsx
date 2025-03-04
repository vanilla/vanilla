/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AISuggestions } from "@dashboard/aiSuggestions/AISuggestions";
import { INITIAL_AISUGGESTION_SETTINGS } from "@dashboard/aiSuggestions/AISuggestions.types";
import {
    AISuggestionSectionSchema,
    getInitialSettings,
    getSettingsSchemaSections,
} from "@dashboard/aiSuggestions/settingsSchemaUtils";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { act, render, RenderResult } from "@testing-library/react";
import MockAdapter from "axios-mock-adapter/types";
import { LiveAnnouncer } from "react-aria-live";

const MOCK_SOURCES = {
    category: {
        enabledLabel: "Enable this source",
        exclusionChoices: {
            api: {
                extraLabelKey: null,
                labelKey: "name",
                searchUrl: "/test-endpoint?query=%s",
                singleUrl: "/test-endpoint/%s",
                valueKey: "uid",
            },
        },
        exclusionLabel: "Exclude these from the source",
    },
};

function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                {children}
            </CurrentUserContextProvider>
        </QueryClientProvider>
    );
    return Wrapper;
}

describe("AISuggestions Settings", () => {
    let mockAdapter: MockAdapter;
    let result: RenderResult;

    beforeEach(async () => {
        mockAdapter = mockAPI();
        setMeta("suggestionSources", MOCK_SOURCES);
        const QueryClientWrapper = queryClientWrapper();
        await act(async () => {
            result = render(
                <QueryClientWrapper>
                    <LiveAnnouncer>
                        <AISuggestions />
                    </LiveAnnouncer>
                </QueryClientWrapper>,
            );
        });
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("Renders error that Q&A addon is not enabled", async () => {
        const warningTitle = await result.findByText(/Feature is not configured/);
        expect(warningTitle).toBeInTheDocument();
    });

    it("Creates initial values from schema", () => {
        const sections = getSettingsSchemaSections({
            ...INITIAL_AISUGGESTION_SETTINGS,
            name: "Scuppy",
        });
        const initialValues = getInitialSettings(sections as AISuggestionSectionSchema[]);
        expect(initialValues.name).toBe("Scuppy");
        expect(initialValues.sources.enabled).toContain("category");
    });
});
