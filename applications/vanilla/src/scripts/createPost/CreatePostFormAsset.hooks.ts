/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { IGroup } from "@groups/groups/Group.types";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { ITag } from "@library/features/tags/TagsReducer";
import { useToast } from "@library/features/toaster/ToastContext";
import { getRelativeUrl } from "@library/utility/appUtils";
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { logError, RecordID, slugify } from "@vanilla/utils";
import { useHistory } from "react-router";

interface MutationArgs {
    endpoint: string;
    body: ICreatePostForm;
}

export interface ICreatePostForm {
    body: string | MyValue;
    format: string;
    name: string;
    categoryID?: ICategory["categoryID"];
    groupID?: IGroup["groupID"];
    pinLocation: "none" | "category" | "recent";
    postTypeID: PostType["postTypeID"];
    postMeta?: Record<PostField["postFieldID"], RecordID | boolean>;
    tags?: Array<ITag["tagID"]>;
    discussionID?: IDiscussion["discussionID"];
}

export const usePostMutation = (tagsToAssign?: Array<ITag["tagID"]>, tagsToCreate?: string[]) => {
    const history = useHistory();
    const { addToast } = useToast();
    const queryClient = useQueryClient();
    const newTagsMutation = useMakeNewTagsMutation();
    const tagPostMutation = useTagPostMutation();

    return useMutation<IDiscussion, IApiError, MutationArgs>({
        mutationKey: ["newPost"],
        mutationFn: async (mutationArgs: MutationArgs) => {
            const { endpoint, body } = mutationArgs;
            const { discussionID, ...bodyNoID } = body || {};

            const response = await (discussionID
                ? apiv2.patch(`/discussions/${discussionID}`, bodyNoID)
                : apiv2.post(endpoint, body));

            let newTagIDs: Array<ITag["tagID"]> = [];

            if (tagsToCreate && tagsToCreate.length > 0) {
                const newTags = await newTagsMutation.mutateAsync(tagsToCreate);
                newTagIDs = newTags.map((tag) => tag.tagID);
            }

            const addTags = [...(tagsToAssign ?? []), ...newTagIDs];

            if (addTags.length > 0) {
                await tagPostMutation.mutateAsync({
                    discussionID: discussionID ?? response.data.discussionID,
                    tagsToAssign: addTags,
                });
            }
            return response.data;
        },
        onSuccess: (data: IDiscussion, variables: MutationArgs) => {
            const { body } = variables;
            const { discussionID } = body || {};

            addToast({
                autoDismiss: true,
                body: discussionID ? t("Success! Post updated") : t("Success! Post created"),
            });
            void queryClient.invalidateQueries(["discussionList"]);
            window.location.href = data.canonicalUrl;
        },
        onError: (error) => {
            logError(error);
            addToast({
                autoDismiss: false,
                dismissible: true,
                body: t("Error. Post could not be created."),
            });
            return error.response.data;
        },
    });
};

export const useMakeNewTagsMutation = () => {
    const { addToast } = useToast();

    const makeTag = async (tagName: ITag["name"]): Promise<ITag> => {
        const createTagBody = {
            name: tagName,
            url: slugify(tagName),
        };
        const response = await apiv2.post("/tags", createTagBody);
        return response.data;
    };

    return useMutation<ITag[], IApiError, string[]>({
        mutationFn: async (newTagNames) => {
            const newTags = await Promise.all(newTagNames.map(makeTag));
            return newTags;
        },
        onSettled: async (data) => {
            return data;
        },
        onError: (error) => {
            logError(error);
            addToast({
                autoDismiss: false,
                dismissible: true,
                body: t("Error. Some new tags could not be created."),
            });
            return error.response.data;
        },
    });
};

interface TagPostMutationParams {
    discussionID: IDiscussion["discussionID"];
    tagsToAssign: Array<ITag["tagID"]>;
}

export const useTagPostMutation = () => {
    const { addToast } = useToast();
    return useMutation<ITag | ITag, IApiError, TagPostMutationParams>({
        mutationFn: async (params) => {
            const response = await apiv2.put(`/discussions/${params.discussionID}/tags`, {
                tagIDs: params.tagsToAssign,
            });
            return response.data;
        },
        onSettled: async (data) => {
            return data;
        },
        onError: (error) => {
            logError(error);
            addToast({
                autoDismiss: false,
                dismissible: true,
                body: t("Error. Unable to tag post."),
            });
            return error.response.data;
        },
    });
};
