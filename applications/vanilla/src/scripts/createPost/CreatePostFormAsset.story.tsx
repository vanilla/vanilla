/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { STORY_DISCUSSION } from "@library/storybook/storyData";
import { ComponentProps } from "react";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";

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

interface IWrappedNewPostForm {
    componentProps?: Partial<ComponentProps<typeof CreatePostFormAsset>>;
}

function WrappedNewPostForm(props: IWrappedNewPostForm) {
    const category = props.componentProps?.category ?? (STORY_DISCUSSION.category as ICategory);
    return (
        <QueryClientProvider client={queryClient}>
            <CreatePostFormAsset category={category} />
        </QueryClientProvider>
    );
}

export function Default() {
    return <WrappedNewPostForm />;
}
