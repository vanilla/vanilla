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
    getSettingsSchema,
} from "@dashboard/aiSuggestions/settingsSchemaUtils";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render } from "@testing-library/react";
import MockAdapter from "axios-mock-adapter/types";

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

    beforeEach(() => {
        mockAdapter = mockAPI();
        setMeta("suggestionSources", MOCK_SOURCES);
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("Renders error that Q&A addon is not enabled", async () => {
        const QueryClientWrapper = queryClientWrapper();
        const result = await render(
            <QueryClientWrapper>
                <AISuggestions />
            </QueryClientWrapper>,
        );
        const warningTitle = result.getByText(/Feature is not configured/);
        expect(warningTitle).toBeInTheDocument();
    });

    it("Creates the schema from the settings and sources in meta", () => {
        const sections = getSettingsSchema(INITIAL_AISUGGESTION_SETTINGS);
        expect(sections).toBeDefined();
        expect(sections?.length).toEqual(3);
        sections?.forEach(({ schema }) => {
            expect(schema.properties).toBeDefined();
        });
        const sources = sections?.[2].schema.properties.sources.properties;
        expect(sources).toBeDefined();
        expect(sources.enabled).toBeDefined();
        expect(sources.enabled.default).toContain("category");
        expect(sources.exclusions).toBeDefined();
        expect(sources.exclusions.properties.category).toBeDefined();
    });

    it("Creates intial values from schema", () => {
        const sections = getSettingsSchema({
            ...INITIAL_AISUGGESTION_SETTINGS,
            name: "Scuppy",
        });
        const initialValues = getInitialSettings(sections as AISuggestionSectionSchema[]);
        expect(initialValues.toneOfVoice).toBe("friendly");
        expect(initialValues.name).toBe("Scuppy");
        expect(initialValues.sources.enabled).toContain("category");
    });
});
