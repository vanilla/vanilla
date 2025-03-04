/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import apiv2 from "@library/apiv2";
import { RecordID } from "@vanilla/utils";
import { ICategory } from "../categories/categoriesTypes";

export namespace DiscussionsApi {
    export interface IndexParams {}

    export interface GetParams {
        expand?: string[];
    }

    export interface PatchParams extends Omit<Partial<IDiscussion>, "discussionID" | "insertUserID"> {
        insertUserID?: RecordID;
    }

    export interface DismissParams {
        dismissed?: boolean;
    }

    export interface MoveParams {
        discussionIDs: Array<IDiscussion["discussionID"]>;
        postTypeID: PostType["postTypeID"];
        categoryID: ICategory["categoryID"];
        addRedirects: boolean;
    }
}

export const DiscussionsApi = {
    get: async (
        discussionID: IDiscussion["discussionID"],
        apiParams: DiscussionsApi.GetParams,
    ): Promise<IDiscussion> => {
        const result = await apiv2.get<IDiscussion>(`/discussions/${discussionID}`, {
            params: apiParams,
        });
        return result.data;
    },

    patch: async (
        discussionID: IDiscussion["discussionID"],
        apiParams: DiscussionsApi.PatchParams,
    ): Promise<IDiscussion> => {
        const result = await apiv2.patch<IDiscussion>(`/discussions/${discussionID}`, apiParams);
        return result.data;
    },

    dismiss: async (discussionID: IDiscussion["discussionID"], apiParams: DiscussionsApi.DismissParams) => {
        const result = await apiv2.put<{ dismissed: boolean }>(`/discussions/${discussionID}/dismiss`, apiParams);
        return result.data;
    },

    bump: async (discussionID: IDiscussion["discussionID"]) => {
        const result = await apiv2.patch<IDiscussion>(`/discussions/${discussionID}/bump`);
        return result.data;
    },

    move: async (apiParams: DiscussionsApi.MoveParams) => {
        await apiv2.patch(`/discussions/move`, apiParams);
    },
};
