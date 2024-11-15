/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    usePostFieldDelete,
    usePostFieldPatchMutation,
    usePostFieldPostMutation,
    usePostFieldQuery,
    usePostFieldSortMutation,
    usePostTypeMutation,
    usePostTypeQuery,
} from "@dashboard/postTypes/postType.hooks";
import { PostType, PostField, PostTypePostParams } from "@dashboard/postTypes/postType.types";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { useQueryClient, UseQueryResult } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { createContext, useContext, useEffect, useState } from "react";
import { useHistory } from "react-router";

export type PostFieldSubmit = Omit<Partial<PostField>, "postTypeID"> & { postTypeID?: PostField["postTypeID"] | null };

export interface IPostTypeEditContext {
    mode: "edit" | "copy" | "new";
    postTypeID: PostType["postTypeID"] | null;
    postType: UseQueryResult<PostType[], IError>;
    allPostFields: PostField[];
    postFieldsByPostTypeID: Record<PostType["postTypeID"], PostField[]>;
    dirtyPostType: Partial<PostTypePostParams> | null;
    updatePostType: (newValue: Partial<PostTypePostParams>) => void;
    savePostType: () => Promise<void>;
    addPostField: (postField: PostFieldSubmit) => void;
    removePostField: (postField: PostField) => void;
    reorderPostFields: (postFields: Record<PostField["postFieldID"], number>) => void;
    isDirty: boolean;
    isLoading: boolean;
    isSaving: boolean;
    error: IError | null;
}

export const PostTypeEditContext = createContext<IPostTypeEditContext>({
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
});

export function usePostTypeEdit() {
    return useContext(PostTypeEditContext);
}

interface IProps extends React.PropsWithChildren<{}> {
    postTypeID: PostType["postTypeID"];
    mode: IPostTypeEditContext["mode"];
}

const INITIAL_VALUES: Partial<PostTypePostParams> = {
    postTypeID: "-1",
    name: "Custom Discussion",
    roleIDs: [],
};

export function PostTypeEditProvider(props: IProps) {
    const { children } = props;
    const queryClient = useQueryClient();
    const history = useHistory();
    const { addToast } = useToast();
    const postTypesQuery = usePostTypeQuery({ postTypeID: props.postTypeID ?? -1 });
    const mutatePostType = usePostTypeMutation();
    const allPostFieldsQuery = usePostFieldQuery();
    const postPostField = usePostFieldPostMutation();
    const patchPostField = usePostFieldPatchMutation();
    const deletePostField = usePostFieldDelete(props.postTypeID);
    const putPostField = usePostFieldSortMutation(props.postTypeID ?? "-1");

    const [isDirty, setIsDirty] = useState(false);
    const [dirtyPostType, setDirtyPostType] = useState<Partial<PostTypePostParams> | null>(INITIAL_VALUES);
    const [dirtyPostFields, setDirtyFields] = useState<PostField[]>([]);
    const [dirtyPostFieldsOrder, setDirtyFieldsOrder] = useState<Record<PostField["postFieldID"], number>>({});
    const [fieldsToDelete, setFieldsToDelete] = useState<PostField[]>([]);
    // We need to maintain this list to facilitate editing of existing fields without persisting them to the server
    const [allPostFields, setAllPostFields] = useState<PostField[]>(allPostFieldsQuery.data ?? []);

    useEffect(() => {
        if (props.mode === "edit" && postTypesQuery.data && postTypesQuery.data.length > 0) {
            const singularPostType = postTypesQuery.data[0];
            if (singularPostType) {
                setDirtyPostType({
                    ...singularPostType,
                    roleIDs:
                        typeof singularPostType?.roleIDs === "string"
                            ? JSON.parse(singularPostType.roleIDs)
                            : singularPostType.roleIDs ?? [],
                });
            }
        }
    }, [props.mode, postTypesQuery.data]);

    useEffect(() => {
        if (allPostFieldsQuery.data) {
            setAllPostFields(allPostFieldsQuery.data);
            setDirtyFields([]);
            setDirtyFieldsOrder({});
        }
    }, [allPostFieldsQuery.data]);

    const [postFieldsByPostTypeID, setPostFieldsByPostTypeID] = useState<Record<PostType["postTypeID"], PostField[]>>(
        {},
    );

    useEffect(() => {
        const grouped = [...allPostFields, ...dirtyPostFields]?.reduce((acc, postField) => {
            return {
                ...acc,
                [postField.postTypeID]: [...(acc[postField.postTypeID] ?? []), postField].sort((a, b) =>
                    a.sort > b.sort ? 1 : -1,
                ),
            };
        }, {});
        setPostFieldsByPostTypeID(grouped);
    }, [allPostFields, dirtyPostFields]);

    const updatePostType = (newValue) => {
        setIsDirty(true);
        setDirtyPostType((prev) => ({ ...prev, ...newValue }));
    };

    const addPostField = (postField: PostField) => {
        setIsDirty(true);
        const { postFieldID } = postField;
        const editedExistingServer = allPostFields.find((field) => field.postFieldID === postFieldID);
        const editedExistingNew = dirtyPostFields.find((field) => field.postFieldID === postFieldID);
        // Check if the field is already on the server and needs updating
        if (editedExistingServer) {
            // Pluck it out of the list
            const filtered = allPostFields.filter((field) => field.postFieldID !== postFieldID);
            setAllPostFields(filtered);
            // Add it to the dirty fields
            setDirtyFields((prev) => [...prev, postField]);
        }
        // Check if the field is already in the dirty list and needs updating
        if (editedExistingNew) {
            setDirtyFields((prev) => {
                return prev.map((field) => {
                    if (field.postFieldID === postFieldID) {
                        return postField;
                    }
                    return field;
                });
            });
        }
        // If the field is new, add it to the dirty list
        if (!editedExistingServer && !editedExistingNew) {
            const total = postFieldsByPostTypeID[postField.postTypeID]
                ? Math.max(...postFieldsByPostTypeID[postField.postTypeID].map(({ sort }) => Number(sort)))
                : 0;
            setDirtyFields((prev) => [...prev, { ...postField, sort: total + 1 }]);
        }
    };

    const removePostField = (postField: PostField) => {
        setIsDirty(true);
        const { postFieldID } = postField;
        const editedExistingServer = allPostFields.find((field) => field.postFieldID === postFieldID);
        const editedExistingNew = dirtyPostFields.find((field) => field.postFieldID === postFieldID);
        if (editedExistingServer) {
            setAllPostFields((prev) => prev.filter((field) => field.postFieldID !== postFieldID));
        }
        if (editedExistingNew) {
            setDirtyFields((prev) => prev.filter((field) => field.postFieldID !== postFieldID));
        }
        setFieldsToDelete((prev) => [...prev, postField]);
    };

    const savePostType = async () => {
        let postTypeIDCache = dirtyPostType?.postTypeID ?? "";
        if (dirtyPostType) {
            await mutatePostType.mutateAsync({
                postTypeID: props.postTypeID,
                body: dirtyPostType,
            });
        }
        // Before we can persist the new order, we need to ensure any newly added fields are saved
        if (dirtyPostFields.length > 0) {
            const existingIDs = allPostFieldsQuery.data?.map((field) => field.postFieldID) ?? [];
            await Promise.all(
                dirtyPostFields.map(async (postField) => {
                    if (existingIDs.includes(postField.postFieldID)) {
                        await patchPostField.mutateAsync({
                            postTypeID: postTypeIDCache,
                            postFieldID: postField.postFieldID,
                            body: postField,
                        });
                    } else {
                        await postPostField.mutateAsync({
                            body: {
                                ...postField,
                                postTypeID: postTypeIDCache,
                            },
                        });
                    }
                }),
            );
        }
        if (Object.keys(dirtyPostFieldsOrder).length > 0) {
            await putPostField.mutateAsync(dirtyPostFieldsOrder);
        }
        if (fieldsToDelete.length > 0) {
            await Promise.all(
                fieldsToDelete.map(async (postField) => {
                    await deletePostField.mutateAsync(postField.postFieldID);
                }),
            );
            setFieldsToDelete([]);
        }
        queryClient.invalidateQueries(["postTypes"]);
        queryClient.invalidateQueries(["postFields"]);
        setIsDirty(false);
        addToast({
            autoDismiss: true,
            body: <>{t("Post type saved successfully")}</>,
        });
        if (props.mode === "new" && postTypeIDCache) {
            history.push(`/settings/post-types/edit/${postTypeIDCache}`);
        }
    };

    const reorderPostFields = async (postFields: Record<PostField["postFieldID"], number>) => {
        setIsDirty(true);
        setDirtyFieldsOrder(postFields);
        setPostFieldsByPostTypeID((prev) => {
            const updatedOrder = postFieldsByPostTypeID[props.postTypeID]
                .map((field) => {
                    return {
                        ...field,
                        sort: postFields[field.postFieldID],
                    };
                })
                .sort((a, b) => (a.sort > b.sort ? 1 : -1));
            return { ...prev, [props.postTypeID]: updatedOrder };
        });
    };

    return (
        <PostTypeEditContext.Provider
            value={{
                mode: props.mode,
                postTypeID: props.postTypeID ?? "-1",
                postType: postTypesQuery,
                allPostFields: allPostFieldsQuery.data ?? [],
                postFieldsByPostTypeID,
                dirtyPostType,
                updatePostType,
                savePostType,
                addPostField,
                removePostField,
                reorderPostFields,
                isDirty,
                isLoading: postTypesQuery.isLoading || allPostFieldsQuery.isLoading,
                isSaving: mutatePostType.isLoading,
                error: mutatePostType.error,
            }}
        >
            {children}
        </PostTypeEditContext.Provider>
    );
}
