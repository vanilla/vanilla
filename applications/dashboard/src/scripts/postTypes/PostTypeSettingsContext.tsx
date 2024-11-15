/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";
import { PostType } from "@dashboard/postTypes/postType.types";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { QueryObserverResult, useMutation, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import throttle from "lodash-es/throttle";
import { createContext, ReactNode, useContext, useEffect, useState } from "react";

export interface IPostTypesSettingsContext {
    postTypes: PostType[];
    postTypesByPostTypeID: Record<PostType["postTypeID"], PostType>;
    status: Record<string, QueryObserverResult["status"]>;
    toggleActive: (postTypeID: PostType["postTypeID"]) => void;
}

export const PostTypesSettingsContext = createContext<IPostTypesSettingsContext>({
    postTypes: [],
    postTypesByPostTypeID: {},
    status: {},
    toggleActive: (postTypeID: PostType["postTypeID"]) => null,
});

export function usePostTypesSettings() {
    return useContext(PostTypesSettingsContext);
}

export function PostTypesSettingsProvider(props: { children: ReactNode }) {
    const { children } = props;
    const postTypesQuery = usePostTypeQuery();
    const { addToast } = useToast();
    const [postTypesList, setPostTypesList] = useState(postTypesQuery.data ?? []);
    const [postTypesByPostTypeID, setPostTypesByPostTypeID] = useState<Record<PostType["postTypeID"], PostType>>({});
    let cache: Record<PostType["postTypeID"], PostType["isActive"]> = {};

    useEffect(() => {
        setPostTypesList(postTypesQuery.data ?? []);
        setPostTypesByPostTypeID((prev) => {
            const newPostTypesByPostTypeID = postTypesQuery.data?.reduce((acc, postType) => {
                return {
                    ...acc,
                    [postType.postTypeID]: postType,
                };
            }, {});
            return newPostTypesByPostTypeID ?? prev;
        });
    }, [postTypesQuery.data]);

    const queryClient = useQueryClient();

    const makeRequestPromise = async (id: PostType["postTypeID"], state: PostType["isActive"]) => {
        return await apiv2.patch<PostType[]>(`/post-types/${id}`, { isActive: state });
    };

    const mutatePostTypeActive = useMutation<PostType[], Error, Record<PostType["postTypeID"], PostType["isActive"]>>({
        mutationFn: async (changes) => {
            const promises = Object.keys(changes).map((key) => {
                return makeRequestPromise(key, changes[key]);
            });
            const responses = await Promise.all(promises);
            const data = responses.map((response) => response.data).flat();
            return data;
        },
        onSuccess: () => {
            addToast({
                autoDismiss: true,
                body: <>{t("Changes successfully saved")}</>,
            });
        },
        onSettled: (data) => {
            queryClient.invalidateQueries(["postTypes"]);
        },
    });

    const dispatchFunction = throttle(
        () => {
            // Only dispatch if there are changes
            if (Object.keys(cache).length > 0) {
                mutatePostTypeActive.mutateAsync(cache);
                cache = {};
            }
        },
        1000,
        { leading: false, trailing: true },
    );

    // Cache the changes that need to be persisted
    const toggleActive = (postTypeID: PostType["postTypeID"]) => {
        // Make new cache state
        const newValue = {
            [postTypeID]: !postTypesList?.find((postType) => postType.postTypeID === postTypeID)?.isActive,
        };
        cache = {
            ...cache,
            ...newValue,
        };
        // Call the throttled mutate function
        dispatchFunction();
    };

    return (
        <PostTypesSettingsContext.Provider
            value={{
                status: {
                    postTypes: postTypesQuery.status,
                },
                postTypes: postTypesList,
                postTypesByPostTypeID,
                toggleActive,
            }}
        >
            {children}
        </PostTypesSettingsContext.Provider>
    );
}
