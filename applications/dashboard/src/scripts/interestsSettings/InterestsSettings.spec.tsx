/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import MockAdapter from "axios-mock-adapter";
import { InterestsSettings } from "./InterestsSettings";
import { AddInterest } from "./AddInterest";
import { ApiV2Context } from "@library/apiv2";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { createReducer } from "@reduxjs/toolkit";
import { mockRolesState } from "@dashboard/components/panels/MembersSearchFilterPanel.spec";

function queryClientWrapper() {
    const queryClient = new QueryClient();

    const Wrapper = ({ children }) => <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
    return Wrapper;
}

function MockInterestsSettings() {
    const QueryClientWrapper = queryClientWrapper();
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        extraReducers: {
            roles: createReducer(mockRolesState, () => {}),
        },
    });
    return (
        <QueryClientWrapper>
            <MockProfileFieldsProvider>
                <ApiV2Context>
                    <InterestsSettings />
                </ApiV2Context>
            </MockProfileFieldsProvider>
        </QueryClientWrapper>
    );
}

function MockAddInterest() {
    const QueryClientWrapper = queryClientWrapper();

    const interest = {
        interestID: 2,
        apiName: "crafting",
        name: "Crafting",
        profileFieldMapping: {
            interests: ["Crafting"],
        },
        categoryIDs: [7, 2],
        tagIDs: [21],
        isDefault: false,
        profileFields: [
            {
                apiName: "interests",
                label: "Interests",
                mappedValue: ["Crafting"],
                options: ["Movies", "Crafting", "Golf", "Football", "Cooking", "Baking"],
            },
        ],
        categories: [
            {
                categoryID: 7,
                name: "Crochet",
            },
            {
                categoryID: 2,
                name: "Q&A",
            },
        ],
        tags: [
            {
                tagID: 21,
                fullName: "crochet",
            },
        ],
    };

    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        extraReducers: {
            roles: createReducer(mockRolesState, () => {}),
        },
    });

    return (
        <QueryClientWrapper>
            <MockProfileFieldsProvider>
                <AddInterest />
            </MockProfileFieldsProvider>
        </QueryClientWrapper>
    );
}

const MOCK_INTERESTS = [
    {
        interestID: 2,
        apiName: "crafting",
        name: "Crafting",
        profileFieldMapping: {
            interests: ["Crafting"],
        },
        categoryIDs: [7, 2],
        tagIDs: [21],
        isDefault: false,
        profileFields: [
            {
                apiName: "interests",
                label: "Interests",
                mappedValue: ["Crafting"],
                options: ["Movies", "Crafting", "Golf", "Football", "Cooking", "Baking"],
            },
        ],
        categories: [
            {
                categoryID: 7,
                name: "Crochet",
            },
            {
                categoryID: 2,
                name: "Q&A",
            },
        ],
        tags: [
            {
                tagID: 21,
                fullName: "crochet",
            },
        ],
    },
    {
        interestID: 3,
        apiName: "magic",
        name: "Magic",
        profileFieldMapping: {
            interests: ["Movies"],
        },
        categoryIDs: [6],
        tagIDs: [],
        isDefault: false,
        profileFields: [
            {
                apiName: "interests",
                label: "Interests",
                mappedValue: ["Movies"],
                options: ["Movies", "Crafting", "Golf", "Football", "Cooking", "Baking"],
            },
        ],
        categories: [
            {
                categoryID: 6,
                name: "Magic",
            },
        ],
        tags: [],
    },
    {
        interestID: 5,
        apiName: "hello",
        name: "Hello Test",
        profileFieldMapping: {
            interests: ["Crafting", "Movies"],
        },
        categoryIDs: [],
        tagIDs: [21],
        isDefault: true,
        profileFields: [
            {
                apiName: "interests",
                label: "Interests",
                mappedValue: ["Crafting", "Movies"],
                options: ["Movies", "Crafting", "Golf", "Football", "Cooking", "Baking"],
            },
        ],
        categories: [],
        tags: [
            {
                tagID: 21,
                fullName: "crochet",
            },
        ],
    },
];

const MOCK_INTERESTS_FILTERED = [
    {
        interestID: 2,
        apiName: "crafting",
        name: "Crafting",
        profileFieldMapping: {
            interests: ["Crafting"],
        },
        categoryIDs: [7, 2],
        tagIDs: [21],
        isDefault: false,
        profileFields: [
            {
                apiName: "interests",
                label: "Interests",
                mappedValue: ["Crafting"],
                options: ["Movies", "Crafting", "Golf", "Football", "Cooking", "Baking"],
            },
        ],
        categories: [
            {
                categoryID: 7,
                name: "Crochet",
            },
            {
                categoryID: 2,
                name: "Q&A",
            },
        ],
        tags: [
            {
                tagID: 21,
                fullName: "crochet",
            },
        ],
    },
    {
        interestID: 5,
        apiName: "hello",
        name: "Hello Test",
        profileFieldMapping: {
            interests: ["Crafting", "Movies"],
        },
        categoryIDs: [],
        tagIDs: [21],
        isDefault: true,
        profileFields: [
            {
                apiName: "interests",
                label: "Interests",
                mappedValue: ["Crafting", "Movies"],
                options: ["Movies", "Crafting", "Golf", "Football", "Cooking", "Baking"],
            },
        ],
        categories: [],
        tags: [
            {
                tagID: 21,
                fullName: "crochet",
            },
        ],
    },
];

describe("InterestsSettings", () => {
    describe("Interest Settings Page", () => {
        let mockAdapter: MockAdapter;

        beforeEach(() => {
            mockAdapter = mockAPI();
            mockAdapter.onGet(/profile-fields/).reply(200, {});
            mockAdapter.onGet(/interests/).reply(200, MOCK_INTERESTS, { "x-app-page-result-count": 3 });
        });

        afterEach(() => {
            mockAdapter.reset();
            setMeta("suggestedContentEnabled", false);
        });

        it("The interest table and add button are not available if suggested content is disabled", async () => {
            render(<MockInterestsSettings />);
            const enableToggle = screen.getByRole("checkbox", {
                name: "Enable Suggested Content and Interest Mapping",
            });
            expect(enableToggle).toBeInTheDocument();
            expect(enableToggle).not.toBeChecked();
            const addButton = await screen.queryByRole("button", { name: "Add Interest" });
            expect(addButton).toBeNull();
            const table = await screen.queryByRole("table");
            expect(table).toBeNull();
        });

        it("The interest table and add button are available if suggested content is enabled in site meta", async () => {
            setMeta("suggestedContentEnabled", true);
            render(<MockInterestsSettings />);
            const enableToggle = screen.getByRole("checkbox", {
                name: "Enable Suggested Content and Interest Mapping",
            });
            expect(enableToggle).toBeInTheDocument();
            expect(enableToggle).toBeChecked();
            const addButton = screen.getByRole("button", { name: "Add Interest" });
            expect(addButton).toBeInTheDocument();
            const table = screen.getByRole("table");
            expect(table).toBeInTheDocument();
        });

        it("The interest table becomes visible when the suggested content is toggled on", async () => {
            mockAdapter
                .onPut("/interests/toggle-suggested-content", { enabled: true })
                .replyOnce(200, { enabled: true });
            render(<MockInterestsSettings />);
            const enableToggle = screen.getByRole("checkbox", {
                name: "Enable Suggested Content and Interest Mapping",
            });
            expect(enableToggle).toBeInTheDocument();
            expect(enableToggle).not.toBeChecked();
            const table = await screen.queryByRole("table");
            expect(table).toBeNull();
            await userEvent.click(enableToggle);
            await waitFor(() => {
                expect(enableToggle).toBeChecked();
                expect(screen.getByRole("table")).toBeInTheDocument();
            });
        });

        it("Renders the fetched interests in the table", async () => {
            setMeta("suggestedContentEnabled", true);
            render(<MockInterestsSettings />);
        });

        it("Opens the Add Interest form when the button is clicked", async () => {
            setMeta("suggestedContentEnabled", true);
            render(<MockInterestsSettings />);
            const addButton = screen.getByRole("button", { name: "Add Interest" });
            expect(addButton).toBeInTheDocument();
            await userEvent.click(addButton);
            const dialog = await screen.findByRole("dialog");
            expect(dialog).toBeInTheDocument();
            const formHeader = within(dialog).getByRole("heading", { name: "Add Interest" });
            expect(formHeader).toBeInTheDocument();
        });
    });

    describe("Add/Edit Interest", () => {
        let mockAdapter: MockAdapter;

        beforeEach(() => {
            mockAdapter = mockAPI();
            mockAdapter.onGet(/profile-fields/).reply(200, {});
            mockAdapter.onGet(/interests/).reply(200, MOCK_INTERESTS, { "x-app-page-result-count": 3 });
        });

        afterEach(() => {
            mockAdapter.reset();
            setMeta("suggestedContentEnabled", false);
        });

        it("Renders add interest form with empty values", async () => {
            render(<MockAddInterest />);
        });
    });
});
