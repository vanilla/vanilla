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
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
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

type PostMutationResponse =
    | IDiscussion
    | {
          status: 202;
          message: string;
      };

export const usePostMutation = () => {
    const queryClient = useQueryClient();

    return useMutation<PostMutationResponse, IApiError, MutationArgs>({
        mutationKey: ["newPost"],
        mutationFn: async (mutationArgs: MutationArgs) => {
            const { endpoint, body } = mutationArgs;
            const { discussionID, ...bodyNoID } = body || {};

            const response = await (discussionID
                ? apiv2.patch<PostMutationResponse>(`/discussions/${discussionID}`, bodyNoID)
                : apiv2.post<PostMutationResponse>(endpoint, body));

            return response.data;
        },
        onSuccess: (data: IDiscussion, variables: MutationArgs) => {
            if (variables.body.discussionID) {
                void queryClient.invalidateQueries({
                    queryKey: ["discussion", { discussionID: parseInt(`${variables.body.discussionID}`) }],
                });
            }
            void queryClient.invalidateQueries(["discussionList"]);
        },
        onError: (error) => {
            logError(error);
            return error.response.data;
        },
    });
};
