/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, screen, waitFor } from "@testing-library/react";
import { PostFieldEditModal } from "@dashboard/postTypes/components/PostFieldEditModal";
import { QueryClient, QueryClientProvider, UseQueryResult } from "@tanstack/react-query";
import { ComponentProps } from "react";
import { PostTypeFixture } from "@dashboard/postTypes/__fixtures__/PostTypeFixture";
import { IPostTypeEditContext, PostTypeEditContext } from "@dashboard/postTypes/PostTypeEditContext";
import { PostType } from "@dashboard/postTypes/postType.types";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { mockAPI } from "@library/__tests__/utility";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
            enabled: true,
            staleTime: Infinity,
        },
    },
});

interface WrappedProps {
    componentProps?: Partial<ComponentProps<typeof PostFieldEditModal>>;
    contextValues?: Partial<IPostTypeEditContext>;
}

function WrappedModal(props?: WrappedProps) {
    const { componentProps, contextValues } = props || {};
    const compProps = {
        isVisible: true,
        onConfirm: vi.fn(),
        onCancel: vi.fn(),
        postTypeID: "mock-post-type-id",
        postField: PostTypeFixture.getPostFields(1)[0],
        ...componentProps,
    };
    return (
        <QueryClientProvider client={queryClient}>
            <PostTypeEditContext.Provider
                value={{
                    mode: "edit",
                    postTypeID: null,
                    postType: {} as UseQueryResult<PostType[], IError>,
                    allPostFields: [],
                    postFieldsByPostTypeID: {},
                    dirtyPostType: null,
                    updatePostType: () => null,
                    savePostType: () => new Promise(() => null),
                    addPostField: () => null,
                    removePostField: () => null,
                    reorderPostFields: () => null,
                    isDirty: false,
                    isLoading: false,
                    isSaving: false,
                    error: null,
                    ...contextValues,
                }}
            >
                <PostFieldEditModal {...compProps} />
            </PostTypeEditContext.Provider>
        </QueryClientProvider>
    );
}

describe("PostFieldEditModal", () => {
    beforeEach(() => {
        const mockAdapter = mockAPI();
        mockAdapter
            .onGet("/post-types")
            .reply(200, [
                PostTypeFixture.mockPostType,
                { ...PostTypeFixture.mockPostType, postTypeID: "another-mock-post-type-id" },
            ]);
    });
    it("Displays banner for one post type", async () => {
        render(<WrappedModal />);
        expect(screen.queryByText("Changes to this field will only affect this post type")).toBeInTheDocument();
    });
    it("Displays banner for multiple post types", async () => {
        const componentProps = {
            postField: PostTypeFixture.getPostFields(1, {
                postTypeIDs: ["mock-post-type-id", "another-mock-post-type-id"],
            })[0],
        };
        render(<WrappedModal componentProps={componentProps} />);
        expect(screen.queryByText(/Changes to this field will affect the following post types/)).toBeInTheDocument();
    });
});
