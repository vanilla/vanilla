/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostTypeFixture } from "@dashboard/postTypes/__fixtures__/PostTypeFixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";
import { LayoutQueryContext } from "@library/features/Layout/LayoutQueryProvider";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { CategoryFixture } from "@vanilla/addon-vanilla/categories/__fixtures__/CategoriesFixture";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { ParentRecordContextProvider } from "@vanilla/addon-vanilla/posts/ParentRecordContext";
import { TagFixture } from "@vanilla/addon-vanilla/tag/__fixture__/TagFixture";
import MockAdapter from "axios-mock-adapter";
import { ComponentProps } from "react";
import { LiveAnnouncer } from "react-aria-live";
import { act } from "react-dom/test-utils";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

interface IWrappedCreatePostForm {
    permissions?: string[];
    componentProps?: Partial<ComponentProps<typeof CreatePostFormAsset>>;
}

const mockFetchGroupByID = vitest.fn(() => ({ data: { groupID: 1, name: "Mock group", categoryID: 233 } }));

async function WrappedNewPostForm(
    props?: IWrappedCreatePostForm,
    layoutQueryOverrides?: Partial<ILayoutQuery["params"]>,
) {
    ParentRecordContextProvider.registerOptionalRecordData({
        key: "fetchGroupByID",
        fn: mockFetchGroupByID,
    });

    const layoutQuery: ILayoutQuery = {
        layoutViewType: "mockLayoutType",
        params: {
            parentRecordType: "category",
            parentRecordID: "1",
            ...layoutQueryOverrides,
        },
    };

    const category =
        props?.componentProps?.category ??
        CategoryFixture.getCategories(1, { allowedPostTypeOptions: PostTypeFixture.getPostTypes(3) })[0];

    render(
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider>
                <LiveAnnouncer>
                    <PermissionsFixtures.SpecificPermissions permissions={props?.permissions ?? []}>
                        <LayoutQueryContext.Provider value={{ layoutQuery }}>
                            <ParentRecordContextProvider>
                                <CreatePostFormAsset category={category} forceFormLoaded />
                            </ParentRecordContextProvider>
                        </LayoutQueryContext.Provider>
                    </PermissionsFixtures.SpecificPermissions>
                </LiveAnnouncer>
            </TestReduxProvider>
        </QueryClientProvider>,
    );
}

describe("CreatePostAsset", () => {
    let mockApi: MockAdapter;
    beforeEach(() => {
        mockApi = mockAPI();
        mockApi
            .onGet(/categories\?.+/)
            .reply(200, CategoryFixture.getCategories(5, { allowedPostTypeOptions: PostTypeFixture.getPostTypes(5) }));
        mockApi
            .onGet(/categories\/1/)
            .reply(
                200,
                CategoryFixture.getCategories(1, { allowedPostTypeOptions: PostTypeFixture.getPostTypes(3) })[0],
            );
        mockApi.onGet(/\/tags.+/).reply(200, TagFixture.getTags(5));
        mockApi.onGet(/post-types\/.+/).reply(200, PostTypeFixture.getPostTypes(5));
    });

    it("Pre-populates Category Field", async () => {
        await act(async () => {
            await WrappedNewPostForm();
        });
        await waitFor(async () => {
            expect(screen.getAllByText("Mock Category 1")[0]).toBeInTheDocument();
        });
    });

    it("Filters available post types", async () => {
        await act(async () => {
            await WrappedNewPostForm();
        });
        const postTypeDropdown = screen.getAllByTestId("inputContainer")[1];
        await userEvent.click(postTypeDropdown);

        await waitFor(async () => {
            expect(screen.getByText("Mock Post Type 1")).toBeInTheDocument();
            expect(screen.queryByText("Mock Post Type 5")).not.toBeInTheDocument();
        });
    });

    it("Cannot announce post without permission", async () => {
        await act(async () => {
            await WrappedNewPostForm();
        });
        const announceDropdown = screen.queryByText("Don't announce");
        expect(announceDropdown).not.toBeInTheDocument();
    });

    it("Can announce post with appropriate permission", async () => {
        await act(async () => {
            await WrappedNewPostForm({
                permissions: ["discussion.announce", "discussions.moderate", "community.manage"],
            });
        });
        const announceDropdown = screen.queryByText("Don't announce");
        expect(announceDropdown).toBeInTheDocument();
    });

    it("Pre-populates Group Field", async () => {
        await act(async () => {
            await WrappedNewPostForm({}, { parentRecordType: "group", parentRecordID: "233" });
        });
        await waitFor(async () => {
            expect(mockFetchGroupByID).toHaveBeenCalled();
            expect(screen.getAllByText("Mock group")[0]).toBeInTheDocument();
        });
    });
});
