/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ComponentProps } from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook, act } from "@testing-library/react-hooks";
import { PostTypeEditProvider, usePostTypeEdit } from "@dashboard/postTypes/PostTypeEditContext";
import { PostField } from "@dashboard/postTypes/postType.types";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { mockAPI } from "@library/__tests__/utility";

function wrapper(props?: Partial<ComponentProps<typeof PostTypeEditProvider>>) {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
                enabled: true,
                staleTime: Infinity,
            },
        },
    });
    // eslint-disable-next-line react/display-name
    return ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <PostTypeEditProvider postTypeID={"mock-post-type-id"} mode={"edit"} {...props}>
                {children}
            </PostTypeEditProvider>
        </QueryClientProvider>
    );
}

const MOCK_POST_FIELD: Partial<PostField> = {
    postFieldID: "mock-post-field-id",
    postTypeIDs: ["mock-post-type-id"],
    label: "Mock Post Field Label",
    description: "Mock Post Field Description",
    dataType: CreatableFieldDataType.TEXT,
    formType: CreatableFieldFormType.TEXT,
    visibility: CreatableFieldVisibility.PUBLIC,
    isRequired: true,
    isActive: false,
    sort: 1,
};

let mockAdapter;
describe("PostTypeEditContext", () => {
    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/post-types/").reply(200);
        mockAdapter.onPost("/post-types/").reply(200);
        mockAdapter.onPatch(/post-types\/(.+)/).reply(200);
        mockAdapter.onGet("/post-fields").reply(200);
        mockAdapter.onPost("/post-fields/").reply(200);
        mockAdapter.onPatch(/post-fields\/(.+)/).reply(200);
        mockAdapter.onDelete(/post-fields\/(.+)/).reply(200);
    });

    it("addPostField updates postFieldsByPostTypeID", async () => {
        const { result } = renderHook(() => usePostTypeEdit(), {
            wrapper: wrapper(),
        });
        act(() => {
            result.current.addPostField(MOCK_POST_FIELD);
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(1);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(1);
    });
    it("addPostField updates existing records in postFieldsByPostTypeID", async () => {
        const { result } = renderHook(() => usePostTypeEdit(), {
            wrapper: wrapper(),
        });
        act(() => {
            result.current.addPostField(MOCK_POST_FIELD);
        });
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(1);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"][0].label).toBe("Mock Post Field Label");
        act(() => {
            result.current.addPostField({ ...MOCK_POST_FIELD, label: "Updated Mock Post Field Label" });
        });
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"][0].label).toBe(
            "Updated Mock Post Field Label",
        );
    });
    it("removePostField with delete mode removes postFieldsByPostTypeID and calls server delete", async () => {
        const { result } = renderHook(() => usePostTypeEdit(), {
            wrapper: wrapper(),
        });
        act(() => {
            result.current.addPostField(MOCK_POST_FIELD);
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(1);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(1);
        act(() => {
            result.current.removePostField(MOCK_POST_FIELD as PostField, "delete");
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(0);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toBeUndefined();
        await act(async () => {
            await result.current.savePostType();
        });
        expect(mockAdapter.history.delete.length).toBe(1);
    });
    it("removePostField with unlink mode removes postFieldsByPostTypeID and does not call server delete", async () => {
        const { result } = renderHook(() => usePostTypeEdit(), {
            wrapper: wrapper(),
        });
        act(() => {
            result.current.addPostField(MOCK_POST_FIELD);
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(1);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(1);
        act(() => {
            result.current.removePostField(MOCK_POST_FIELD as PostField, "unlink");
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(0);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toBeUndefined();
        act(() => {
            void result.current.savePostType();
        });
        expect(mockAdapter.history.delete.length).toBe(0);
    });
    it("reorderPostField updates postFieldsByPostTypeID", async () => {
        const { result } = renderHook(() => usePostTypeEdit(), {
            wrapper: wrapper(),
        });
        act(() => {
            result.current.addPostField(MOCK_POST_FIELD);
            result.current.addPostField({ ...MOCK_POST_FIELD, postFieldID: "mock-post-field-id-2", sort: 2 });
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(1);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(2);
        act(() => {
            result.current.reorderPostFields({
                "mock-post-field-id": 2,
                "mock-post-field-id-2": 1,
            });
        });
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(2);
        result.current.postFieldsByPostTypeID["mock-post-type-id"].forEach((postField) => {
            if (postField.postFieldID === "mock-post-field-id") {
                expect(postField.sort).toBe(2);
            }
            if (postField.postFieldID === "mock-post-field-id-2") {
                expect(postField.sort).toBe(1);
            }
        });
    });
    it("Saving clears dirty state", async () => {
        const { result } = renderHook(() => usePostTypeEdit(), {
            wrapper: wrapper(),
        });
        act(() => {
            result.current.addPostField(MOCK_POST_FIELD);
        });
        expect(Object.keys(result.current.postFieldsByPostTypeID)).toHaveLength(1);
        expect(result.current.postFieldsByPostTypeID["mock-post-type-id"]).toHaveLength(1);
        expect(result.current.isDirty).toBe(true);
        await act(async () => {
            await result.current.savePostType();
        });
        expect(result.current.isDirty).toBe(false);
    });
});
