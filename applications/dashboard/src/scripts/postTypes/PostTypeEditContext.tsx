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
import { PostType, PostField, PostTypePostParams, PostFieldDeleteMethod } from "@dashboard/postTypes/postType.types";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { Select } from "@library/json-schema-forms";
import { useQueryClient, UseQueryResult } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import { createContext, useContext, useEffect, useState } from "react";
import { useHistory } from "react-router";

export type PostFieldSubmit = Omit<Partial<PostField>, "postTypeID"> & {
    postTypeIDs?: PostField["postTypeIDs"] | null;
};

export interface IPostTypeEditContext {
    mode: "edit" | "copy" | "new";
    postTypeID: PostType["postTypeID"] | null;
    postType: UseQueryResult<PostType[], IError>;
    allPostFields: PostField[];
    postFieldsByPostTypeID: Record<PostType["postTypeID"], PostField[]>;
    dirtyPostType: Partial<PostTypePostParams> | null;
    initialOptionValues?: Record<string, Select.Option[]>;
    updatePostType: (newValue: Partial<PostTypePostParams>) => void;
    savePostType: () => Promise<void>;
    addPostField: (postField: PostFieldSubmit) => void;
    removePostField: (postField: PostField, deleteMethod: PostFieldDeleteMethod) => void;
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
    name: "",
    roleIDs: [],
};

export function PostTypeEditProvider(props: IProps) {
    const { children } = props;
    const queryClient = useQueryClient();
    const history = useHistory();
    const { addToast } = useToast();
    const postTypesQuery = usePostTypeQuery({ postTypeID: props.postTypeID ?? -1, expand: ["postFields"] });
    const mutatePostType = usePostTypeMutation();
    const allPostFieldsQuery = usePostFieldQuery();
    const postPostField = usePostFieldPostMutation();
    const patchPostField = usePostFieldPatchMutation();
    const deletePostField = usePostFieldDelete();
    const putPostField = usePostFieldSortMutation(props.postTypeID ?? "-1");

    const [isDirty, setIsDirty] = useState(false);
    const [dirtyPostType, setDirtyPostType] = useState<Partial<PostTypePostParams> | null>(INITIAL_VALUES);
    const [dirtyPostFields, setDirtyFields] = useState<PostField[]>([]);
    const [dirtyPostFieldsOrder, setDirtyFieldsOrder] = useState<Record<PostField["postFieldID"], number>>({});
    const [fieldsToDelete, setFieldsToDelete] = useState<Array<PostField["postFieldID"]>>([]);
    const [fieldsToUnlink, setFieldsToUnlink] = useState<Array<PostField["postFieldID"]>>([]);
    // We need to maintain this list to facilitate editing of existing fields without persisting them to the server
    const [allPostFields, setAllPostFields] = useState<PostField[]>(allPostFieldsQuery.data ?? []);
    const [initialOptionValues, setInitialOptionValues] = useState<IPostTypeEditContext["initialOptionValues"]>({});

    const [postFieldIDsByPostTypeID, setPostFieldIDsByPostTypeID] = useState<
        Record<PostType["postTypeID"], Array<PostField["postFieldID"]>>
    >({});

    const getCategories = async (categoryIDs: RecordID[]) => {
        const response = await apiv2.get("/categories", {
            params: {
                categoryID: categoryIDs,
            },
        });
        return response.data;
    };

    const getRoles = async () => {
        const response = await apiv2.get("/roles");
        return response.data;
    };

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
                const categoryIDs = singularPostType.categoryIDs ?? [];
                if (categoryIDs && categoryIDs.length > 0) {
                    void getCategories(categoryIDs).then((categories) => {
                        setInitialOptionValues((prev) => ({
                            ...prev,
                            categoryIDs: categories.map((category: ICategory) => ({
                                value: category.categoryID,
                                label: category.name,
                            })),
                        }));
                    });
                }
                const roleIDs = singularPostType.roleIDs ?? [];

                void getRoles().then((roles) => {
                    setInitialOptionValues((prev) => ({
                        ...prev,
                        roleIDs: roleIDs.map((roleID: RecordID) => ({
                            value: roleID,
                            label: roles.find((role) => role.roleID === roleID)?.name ?? "",
                        })),
                    }));
                });

                setPostFieldIDsByPostTypeID((prev) => {
                    return {
                        ...prev,
                        [singularPostType.postTypeID]: (singularPostType?.postFields ?? []).map(
                            (field) => field.postFieldID,
                        ),
                    };
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
            const IDs = postField.postTypeIDs ?? [];

            IDs.forEach((postTypeID) => {
                acc[postTypeID] = [...(acc[postTypeID] ?? []), postField].sort((a, b) => (a.sort > b.sort ? 1 : -1));
            });

            return acc;
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
            const total = postFieldsByPostTypeID[postField.postTypeIDs.length]
                ? Math.max(...postFieldsByPostTypeID[postField.postTypeIDs.length].map(({ sort }) => Number(sort)))
                : 0;
            setDirtyFields((prev) => [...prev, { ...postField, sort: total + 1 }]);
        }
    };

    const removePostField = (postField: PostField, deleteMethod: PostFieldDeleteMethod) => {
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
        if (deleteMethod === "unlink") {
            setFieldsToUnlink((prev) => [...prev, postField.postFieldID]);
        } else {
            setFieldsToDelete((prev) => [...prev, postField.postFieldID]);
        }
    };

    const savePostType = async () => {
        // Iterate over all the post fields and determine if they are new or need updating
        if (dirtyPostFields.length > 0) {
            const existingIDs = allPostFieldsQuery.data?.map((field) => field.postFieldID) ?? [];
            await Promise.all(
                dirtyPostFields.map(async (postField) => {
                    if (existingIDs.includes(postField.postFieldID)) {
                        await patchPostField.mutateAsync({
                            postFieldID: postField.postFieldID,
                            body: postField,
                        });
                    } else {
                        await postPostField.mutateAsync({
                            body: {
                                ...postField,
                            },
                        });
                    }
                }),
            );
        }
        if (fieldsToDelete.length > 0) {
            await Promise.all(
                fieldsToDelete.map(async (postFieldID) => {
                    await deletePostField.mutateAsync(postFieldID);
                }),
            );
        }
        const postTypeIDCache = dirtyPostType?.postTypeID ?? "";
        // Make list of post fields to apply to the post type and maintain their order
        const dirtyPostFieldIDs = dirtyPostFields.map((field) => field.postFieldID);
        const postFieldIDs = [
            ...new Set([...(postFieldIDsByPostTypeID?.[postTypeIDCache] ?? []), ...dirtyPostFieldIDs]),
        ].filter((id) => {
            return ![...fieldsToUnlink, ...fieldsToDelete].includes(id);
        });
        // Persist updates to the post type, including added, linked and updated post fields
        if (dirtyPostType) {
            await mutatePostType.mutateAsync({
                postTypeID: props.postTypeID,
                body: { ...dirtyPostType, postFieldIDs },
            });
        }
        // Ensure the order of the post fields is updated for that specific post type
        if (Object.keys(dirtyPostFieldsOrder).length > 0) {
            await putPostField.mutateAsync(dirtyPostFieldsOrder);
        }
        setFieldsToDelete([]);
        setFieldsToUnlink([]);
        void queryClient.invalidateQueries(["postTypes"]);
        void queryClient.invalidateQueries(["postFields"]);
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
                initialOptionValues,
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
