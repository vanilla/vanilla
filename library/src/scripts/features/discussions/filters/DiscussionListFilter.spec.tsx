/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ReactNode, useState } from "react";
import { render, screen, act, cleanup, fireEvent } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook } from "@testing-library/react-hooks";
import { mockAPI } from "@library/__tests__/utility";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { useStatusOptions, useTypeOptions } from "./discussionListFilterHooks";
import { stableObjectHash } from "@vanilla/utils";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { DeepPartial } from "redux";
import { DiscussionListFilter } from "./DiscussionListFilter";
import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import MockAdapter from "axios-mock-adapter/types";
import { PermissionsContextProvider } from "@library/features/users/PermissionsContext";

// create a state for the community.manage permission as enabled or disabled
const communityManagePermission = (enable: boolean = false) => ({
    status: LoadStatus.SUCCESS,
    data: {
        isAdmin: false,
        permissions: [
            {
                type: "global",
                id: null,
                permissions: {
                    perm1: true,
                    perm2: true,
                    perm3: true,
                    "community.manage": enable,
                },
            },
        ],
    },
});

// Mock data for addons, used in determining which types to display
const MOCK_ADDONS_OPTIONS = [
    { value: "Discussion", label: "Discussion" },
    { value: "Question", label: "Question" },
    { value: "Idea", label: "Idea" },
];

// Mock data for tags
const MOCK_TAGS_DATA = [
    {
        tagID: 1,
        id: 1,
        name: "Spam",
        type: "Reaction",
        urlCode: "Spam",
        countDiscussions: 0,
    },
    {
        tagID: 5,
        id: 5,
        name: "unicorn",
        type: "User",
        urlCode: "unicorn",
        countDiscussion: 0,
    },
    {
        tagID: 16,
        id: 16,
        name: "Already Offered",
        type: "Status",
        urlCode: "Already Offered",
        countDiscussions: 0,
    },
    {
        tagID: 23,
        id: 23,
        name: "Dragon",
        type: "User",
        urlCode: "User",
        countDiscussion: 0,
    },
];
const MOCK_TAGS_OPTIONS = [
    { value: "23", label: "Dragon" },
    { value: "5", label: "unicorn" },
];

const MOCK_ALL_TAGS_OPTIONS = [
    { value: "16", label: "Already Offered" },
    { value: "23", label: "Dragon" },
    { value: "1", label: "Spam" },
    { value: "5", label: "unicorn" },
];

// Mock data for statuses
const MOCK_STATUS_DATA = [
    {
        statusID: 1,
        name: "Unanswered",
        recordSubtype: "question",
        isInternal: false,
    },
    {
        statusID: 2,
        name: "Answered",
        recordSubtype: "question",
        isInternal: false,
    },
    {
        statusID: 3,
        name: "Rejected",
        recordSubtype: "question",
        isInternal: false,
    },
    {
        statusID: 7,
        name: "Unresolved",
        recordSubtype: "discussion",
        isInternal: true,
    },
    {
        statusID: 8,
        name: "Resolved",
        recordSubtype: "discussion",
        isInternal: true,
    },
    {
        statusID: 9,
        name: "Declined",
        recordSubtype: "ideation",
        isInternal: false,
    },
    {
        statusID: 10,
        name: "Completed",
        recordSubtype: "ideation",
        isInternal: false,
    },
];
const MOCK_STATUS_OPTIONS = [
    {
        label: "Q & A",
        options: [
            { value: 1, label: "Unanswered" },
            { value: 2, label: "Answered" },
        ],
    },
    {
        label: "Ideas",
        options: [
            { value: 10, label: "Completed" },
            { value: 9, label: "Declined" },
        ],
    },
];
const MOCK_INTERNAL_STATUS_OPTIONS = [
    { value: 7, label: "Unresolved" },
    { value: 8, label: "Resolved" },
];

// Mock wrapper with proper context providers for running the tests
interface IMockWrapperProps {
    children: ReactNode;
    isCommunityManager?: boolean;
}

const MockWrapper = (props: IMockWrapperProps) => {
    const { children, isCommunityManager = false } = props;
    const configKeys = ["plugins.qna", "plugins.ideation", "plugins.polls"];
    const mockState: DeepPartial<ICoreStoreState> = {
        users: {
            permissions: communityManagePermission(isCommunityManager),
        },
        config: {
            configsByLookupKey: {
                [stableObjectHash(configKeys)]: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        "plugins.qna": true,
                        "plugins.ideation": true,
                        "plugins.polls": false,
                    },
                },
            },
        },
    };
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    return (
        <TestReduxProvider state={mockState}>
            <QueryClientProvider client={queryClient}>
                <PermissionsContextProvider>
                    <MemoryRouter>{children}</MemoryRouter>
                </PermissionsContextProvider>
            </QueryClientProvider>
        </TestReduxProvider>
    );
};

const MockFilter = (props: Omit<IMockWrapperProps, "children">) => {
    const [apiParams, setApiParams] = useState<IGetDiscussionListParams>({});

    return (
        <MockWrapper isCommunityManager={props.isCommunityManager}>
            <DiscussionListFilter
                apiParams={apiParams}
                updateApiParams={(newValues) => setApiParams({ ...apiParams, ...newValues })}
                forceOpen
            />
        </MockWrapper>
    );
};

describe("DiscussionListFilter", () => {
    // Mock API for the tests
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/tags").reply(200, MOCK_TAGS_DATA);
        mockAdapter.onGet("/discussions/statuses").reply(200, [...MOCK_STATUS_DATA]);
    });

    afterEach(() => {
        mockAdapter.reset();
        cleanup();
    });

    it("useTypeOptions() hook returns options based on enabled addons", async () => {
        const { result, waitFor } = renderHook(() => useTypeOptions(), { wrapper: MockWrapper });

        await waitFor(() => {
            expect(result.current).toBeDefined();
            expect(result.current).toStrictEqual(MOCK_ADDONS_OPTIONS);
        });
    });

    it("useStatusOptions() hook returns Questions and Ideation statuses", async () => {
        const { result, waitFor } = renderHook(() => useStatusOptions(), { wrapper: MockWrapper });

        await vi.dynamicImportSettled();
        await waitFor(() => {
            expect(result.current).toBeDefined();
            expect(result.current).toStrictEqual(MOCK_STATUS_OPTIONS);
        });
    });

    it("useStatusOptions({ internal: true }) hook return Internal Statuses", async () => {
        const { result, waitFor } = renderHook(() => useStatusOptions(true), { wrapper: MockWrapper });
        await vi.dynamicImportSettled();
        await waitFor(() => {
            expect(result.current).toBeDefined();
            expect(result.current).toStrictEqual(MOCK_INTERNAL_STATUS_OPTIONS);
        });
    });

    it("Displays all fields including resolution status and post engagement due to proper permissions", async () => {
        render(<MockFilter isCommunityManager />);
        await vi.dynamicImportSettled();
        expect(await screen.findByText(/Post Type/)).toBeInTheDocument();
        expect(await screen.findByText(/Post Status/)).toBeInTheDocument();
        expect(await screen.findByText(/Tags/)).toBeInTheDocument();
        expect(await screen.findByText(/Resolution Status/)).toBeInTheDocument();
        expect(await screen.findByText(/Post Engagement/)).toBeInTheDocument();
    });

    it("Displays all fields except resolution status and post engagement due to lack of permissions", async () => {
        render(<MockFilter />);

        await vi.dynamicImportSettled();

        expect(screen.getByText(/Post Type/)).toBeInTheDocument();
        expect(await screen.findByText(/Post Status/)).toBeInTheDocument();
        expect(screen.getByText(/Tags/)).toBeInTheDocument();
        expect(screen.queryByText(/Resolution Status/)).not.toBeInTheDocument();
        expect(screen.queryByText(/Post Engagement/)).not.toBeInTheDocument();
    });

    describe("Post engagement section", async () => {
        it("renders both checkboxes as checked by default", async () => {
            render(<MockFilter isCommunityManager />);
            await vi.dynamicImportSettled();
            const hasNoCommentsCheckbox = await screen.findByRole("checkbox", { name: "No Comments" });
            const hasCommentsCheckbox = await screen.findByRole("checkbox", { name: "Has Comments" });

            expect(hasNoCommentsCheckbox).toBeChecked();
            expect(hasCommentsCheckbox).toBeChecked();
        });

        it("allows the user to uncheck a checkbox", async () => {
            render(<MockFilter isCommunityManager />);
            await vi.dynamicImportSettled();
            const hasNoCommentsCheckbox = await screen.findByRole("checkbox", { name: "No Comments" });
            const hasCommentsCheckbox = await screen.findByRole("checkbox", { name: "Has Comments" });

            // Both are checked by default, uncheck 'has no comments'
            await act(async () => {
                fireEvent.click(hasNoCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).not.toBeChecked();
            expect(hasCommentsCheckbox).toBeChecked();

            // re-check 'has no comments' so both are checked again
            await act(async () => {
                fireEvent.click(hasNoCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).toBeChecked();
            expect(hasCommentsCheckbox).toBeChecked();

            // uncheck 'has comments'
            await act(async () => {
                fireEvent.click(hasCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).toBeChecked();
            expect(hasCommentsCheckbox).not.toBeChecked();
        });

        it("won't allow the user to uncheck both checkboxes", async () => {
            render(<MockFilter isCommunityManager />);
            await vi.dynamicImportSettled();
            const hasNoCommentsCheckbox = await screen.findByRole("checkbox", { name: "No Comments" });
            const hasCommentsCheckbox = await screen.findByRole("checkbox", { name: "Has Comments" });

            // Both are checked by default, uncheck 'has no comments'
            await act(async () => {
                fireEvent.click(hasNoCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).not.toBeChecked();
            expect(hasCommentsCheckbox).toBeChecked();

            // Attempt to uncheck 'has comments' too
            await act(async () => {
                fireEvent.click(hasCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).not.toBeChecked();
            // At least one checkbox must be selected, so it can't be unchecked
            expect(hasCommentsCheckbox).toBeChecked();

            // Make both checkboxes checked again
            await act(async () => {
                fireEvent.click(hasNoCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).toBeChecked();
            expect(hasCommentsCheckbox).toBeChecked();

            // Uncheck 'has comments'
            await act(async () => {
                fireEvent.click(hasCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).toBeChecked();
            expect(hasCommentsCheckbox).not.toBeChecked();

            // Attempt to uncheck 'has no comments' too
            await act(async () => {
                fireEvent.click(hasNoCommentsCheckbox);
            });

            expect(hasNoCommentsCheckbox).toBeChecked();
            // At least one checkbox must be selected, so it can't be unchecked
            expect(hasCommentsCheckbox).not.toBeChecked();
        });
    });
});
