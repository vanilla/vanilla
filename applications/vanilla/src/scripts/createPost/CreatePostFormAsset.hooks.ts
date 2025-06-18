/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { IGroup } from "@groups/groups/Group.types";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { ITag } from "@library/features/tags/TagsReducer";
import { useToast } from "@library/features/toaster/ToastContext";
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { logError, RecordID } from "@vanilla/utils";

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
    pinLocation?: "none" | "category" | "recent";
    pinned?: boolean;
    postTypeID: PostType["postTypeID"];
    postMeta?: Record<PostField["postFieldID"], RecordID | boolean>;
    tagIDs?: Array<ITag["tagID"]>;
    newTagNames?: Array<ITag["name"]>;
    discussionID?: IDiscussion["discussionID"];
}

export interface IGetEditPostResponse extends ICreatePostForm {}

export const usePostMutation = () => {
    const { addToast } = useToast();
    const queryClient = useQueryClient();

    return useMutation<IDiscussion, IApiError, MutationArgs>({
        mutationKey: ["newPost"],
        mutationFn: async (mutationArgs: MutationArgs) => {
            const { endpoint, body } = mutationArgs;
            const { discussionID, ...bodyNoID } = body || {};

            const response = await (discussionID
                ? apiv2.patch(`/discussions/${discussionID}`, bodyNoID)
                : apiv2.post(endpoint, body));

            return response.data;
        },
        onSuccess: (data: IDiscussion, variables: MutationArgs) => {
            const { body } = variables;
            const { discussionID } = body || {};

            addToast({
                autoDismiss: true,
                body: discussionID ? t("Success! Post updated") : t("Success! Post created"),
            });
            if (variables.body.discussionID) {
                void queryClient.invalidateQueries({
                    queryKey: ["discussion", { discussionID: parseInt(`${variables.body.discussionID}`) }],
                });
            }
            void queryClient.invalidateQueries(["discussionList"]);
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
