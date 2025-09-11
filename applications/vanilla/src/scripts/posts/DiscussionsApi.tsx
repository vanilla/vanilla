/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import apiv2 from "@library/apiv2";
import { RecordID } from "@vanilla/utils";
import { ICategory } from "../categories/categoriesTypes";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { createContext, useContext } from "react";

export namespace DiscussionsApi {
    export type Discussion = IDiscussion;

    export interface IndexParams {
        postTypeID?: PostType["postTypeID"];
        categoryID?: ICategory["categoryID"];
        discussionID?: Array<IDiscussion["discussionID"]>;
        insertUserID?: Array<IDiscussion["insertUserID"]>;
        sortDirection?: "asc" | "desc";
        limit?: number;
        page?: number;
    }

    export interface GetParams {
        expand?: string[];
    }

    export interface PatchParams extends Omit<Partial<IDiscussion>, "discussionID" | "insertUserID"> {
        insertUserID?: RecordID;
    }

    export interface MuteParams {
        muted?: boolean;
    }

    export interface DismissParams {
        dismissed?: boolean;
    }

    export interface MoveParams {
        discussionIDs: Array<IDiscussion["discussionID"]>;
        categoryID: ICategory["categoryID"];
        postTypeID?: PostType["postTypeID"];
        postMeta?: Record<string, any>;
        addRedirects: boolean;
    }
}

export const DiscussionsApi = {
    index: async (apiParams: DiscussionsApi.IndexParams) => {
        const result = await apiv2.get<IDiscussion[]>("/discussions", {
            params: apiParams,
        });

        const paging = SimplePagerModel.parseHeaders(result.headers);
        return {
            data: result.data,
            paging,
        };
    },

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

    convert: async (
        discussionID: IDiscussion["discussionID"],
        apiParams: DiscussionsApi.PatchParams,
    ): Promise<IDiscussion> => {
        const result = await apiv2.put<IDiscussion>(`/discussions/${discussionID}/type`, apiParams);
        return result.data;
    },

    dismiss: async (discussionID: IDiscussion["discussionID"], apiParams: DiscussionsApi.DismissParams) => {
        const result = await apiv2.put<{ dismissed: boolean }>(`/discussions/${discussionID}/dismiss`, apiParams);
        return result.data;
    },

    mute: async (discussionID: IDiscussion["discussionID"], apiParams: DiscussionsApi.MuteParams) => {
        const result = await apiv2.put<{ muted: boolean }>(`/discussions/${discussionID}/mute`, apiParams);
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

interface IDiscussionsApiContext {
    api: typeof DiscussionsApi;
}

export const DiscussionsApiContext = createContext<IDiscussionsApiContext>({
    api: DiscussionsApi,
});

export function useDiscussionsApiContext() {
    return useContext<IDiscussionsApiContext>(DiscussionsApiContext);
}

export function useDiscussionsApi() {
    return useDiscussionsApiContext().api;
}
