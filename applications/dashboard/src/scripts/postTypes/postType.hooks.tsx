/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    PostField,
    PostFieldGetParams,
    PostFieldPatchParams,
    PostFieldPostParams,
    PostFieldPutParams,
    PostType,
    PostTypeGetParams,
    PostTypePatchParams,
    PostTypePostParams,
} from "@dashboard/postTypes/postType.types";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { debug, logDebug } from "@vanilla/utils";

export function usePostTypeQuery(params?: Partial<PostTypeGetParams>, enabled = true) {
    return useQuery<PostTypeGetParams, IError, PostType[]>({
        queryKey: ["postTypes", params, params?.postTypeID],
        queryFn: async () => {
            if (params?.postTypeID == "-1") {
                return [];
            }

            const response = await apiv2.get("/post-types", {
                params: {
                    ...params,
                },
            });
            return response.data;
        },
        enabled,
    });
}

export interface PostTypeMutateArgs {
    body: PostTypePostParams | PostTypePatchParams;
    postTypeID?: PostType["postTypeID"];
}

export function usePostTypeMutation() {
    return useMutation<PostType, IError, PostTypeMutateArgs>({
        mutationKey: ["postTypesPostOrPatch"],
        mutationFn: async (mutationArgs) => {
            const { body, postTypeID } = mutationArgs;
            const response = await (postTypeID
                ? apiv2.patch(`/post-types/${postTypeID}`, body)
                : apiv2.post("/post-types", body));
            return response.data;
        },
    });
}

export function usePostTypeDelete() {
    const queryClient = useQueryClient();
    const { addToast } = useToast();
    return useMutation<any, IError, PostType["postTypeID"]>({
        mutationKey: ["postTypeDelete"],
        mutationFn: async (postTypeID) => {
            const response = await apiv2.delete(`/post-types/${postTypeID}`);
            return response.data;
        },
        onError: (error) => {
            logDebug("Error deleting post type", error);
            addToast({
                autoDismiss: false,
                dismissible: true,
                body: (
                    <>
                        {t("Post type could not be deleted.")}
                        {debug() && (
                            <>
                                <br />
                                {error.message}
                            </>
                        )}
                    </>
                ),
            });
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["postTypes"]);
            addToast({
                autoDismiss: true,
                body: <>{t("Post type successfully deleted")}</>,
            });
        },
    });
}

export function usePostFieldQuery(params?: Partial<PostFieldGetParams>) {
    return useQuery<PostFieldGetParams, IError, PostField[]>({
        queryKey: ["postFields", params],
        queryFn: async () => {
            const response = await apiv2("/post-fields", {
                params: {
                    ...params,
                },
            });
            return response.data;
        },
        keepPreviousData: true,
    });
}

export function usePostFieldPatchMutation() {
    return useMutation<PostField, IError, { postFieldID: PostField["postFieldID"]; body: PostFieldPatchParams }>({
        mutationKey: ["postFieldPatch"],
        mutationFn: async (mutationArgs) => {
            const { postFieldID, body } = mutationArgs;
            const response = await apiv2.patch(`/post-fields/${postFieldID}`, body);
            return response.data;
        },
    });
}

export function usePostFieldPostMutation() {
    return useMutation<PostField, IError, { body: PostFieldPostParams }>({
        mutationKey: ["postFieldPost"],
        mutationFn: async (mutationArgs) => {
            const { body } = mutationArgs;
            const response = await apiv2.post(`/post-fields/`, body);
            return response.data;
        },
    });
}

export function usePostFieldDelete() {
    const { addToast } = useToast();
    return useMutation<any, IError, PostField["postFieldID"]>({
        mutationKey: ["postTypeDelete"],
        mutationFn: async (postFieldID) => {
            const response = await apiv2.delete(`/post-fields/${postFieldID}`);
            return response.data;
        },
        onError: (error) => {
            logDebug("Error deleting post field", error);
            addToast({
                autoDismiss: false,
                dismissible: true,
                body: (
                    <>
                        {t("Post field could not be deleted.")}
                        {debug() && (
                            <>
                                <br />
                                {error.message}
                            </>
                        )}
                    </>
                ),
            });
        },
    });
}

export function usePostFieldSortMutation(postTypeID: PostType["postTypeID"]) {
    return useMutation<PostField[], IError, PostFieldPutParams>({
        mutationFn: async (order: PostFieldPutParams) => {
            if (postTypeID == "-1") {
                return [];
            }
            const response = await apiv2.put(`/post-fields/${postTypeID}/sorts`, {
                ...order,
            });
            return response.data;
        },
        mutationKey: ["sortPostFields", postTypeID],
    });
}
