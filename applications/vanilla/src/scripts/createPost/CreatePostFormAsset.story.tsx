/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { STORY_DISCUSSION } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import { ComponentProps } from "react";

export default {
    title: "Widgets/CreatePostFormAsset",
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});
let mockApi: MockAdapter;

const mockCategory = STORY_DISCUSSION.category;

mockApi = mockAPI();
mockApi.onGet(`/categories/${mockCategory!.categoryID}`).reply(() => {
    return [200, {}];
});
mockApi.onGet("/categories").reply((config) => {
    if (config.params.categoryID) {
        return [200, {}];
    } else {
        return [200, []];
    }
});

mockApi.onGet("/post-types").reply(() => {
    return [200, []];
});

interface IWrappedNewPostForm {
    componentProps?: Partial<ComponentProps<typeof CreatePostFormAsset>>;
}

function WrappedNewPostForm(props: IWrappedNewPostForm) {
    const category = props.componentProps?.category ?? (STORY_DISCUSSION.category as ICategory);
    return (
        <QueryClientProvider client={queryClient}>
            <CreatePostFormAsset category={category} taggingEnabled />
        </QueryClientProvider>
    );
}

export function Default() {
    return <WrappedNewPostForm />;
}
