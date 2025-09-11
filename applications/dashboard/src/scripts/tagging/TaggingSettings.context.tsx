/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import TagsApi, { ITagsApi } from "@dashboard/tagging/Tags.api";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { IApiError } from "@library/@types/api/core";
import { ITagItem } from "@dashboard/tagging/taggingSettings.types";

interface ITagsApiContext {
    api: ITagsApi;
}

export const TagsApiContext = React.createContext<ITagsApiContext>({
    api: TagsApi,
});

export function useTagsApiContext() {
    return React.useContext<ITagsApiContext>(TagsApiContext);
}

export function useTagsApi() {
    return useTagsApiContext().api;
}

export function usePostTag() {
    const { api } = useTagsApiContext();
    const queryClient = useQueryClient();
    return useMutation<Awaited<ReturnType<ITagsApi["postTag"]>>, IApiError, Parameters<ITagsApi["postTag"]>[0]>({
        mutationKey: ["postTag"],
        mutationFn: async (params) => await api.postTag(params),
        onSuccess: async () => {
            await queryClient.invalidateQueries({ queryKey: ["getTags"] });
        },
    });
}

export function usePatchTag(tagID: ITagItem["tagID"]) {
    const { api } = useTagsApiContext();
    return useMutation<
        Awaited<ReturnType<ITagsApi["patchTag"]>>,
        IApiError,
        Omit<Parameters<ITagsApi["patchTag"]>[0], "tagID">
    >({
        mutationKey: ["patchTag", tagID],
        mutationFn: async (params) =>
            await api.patchTag({
                ...params,
                tagID,
            }),
    });
}

export function useDeleteTag(tagID: ITagItem["tagID"]) {
    const { api } = useTagsApiContext();

    return useMutation<Awaited<ReturnType<ITagsApi["deleteTag"]>>, IApiError, void>({
        mutationKey: ["deleteTag", tagID],
        mutationFn: async () => await api.deleteTag({ tagID }),
    });
}
