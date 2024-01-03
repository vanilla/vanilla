/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import apiv2 from "@library/apiv2";
import { RecordID } from "@vanilla/utils";

export const DiscussionsApi = {
    get: async (apiParams: DiscussionsApi.GetParams): Promise<IDiscussion> => {
        const result = await apiv2.get<IDiscussion>(`/discussions/${apiParams.discussionID}`, {
            params: { expand: ["insertUser", "breadcrumbs", "reactions", "status", "status.log"] },
        });
        return result.data;
    },

    patch: async (apiParams: DiscussionsApi.PatchParams): Promise<IDiscussion> => {
        const { discussionID, ...body } = apiParams;
        const result = await apiv2.patch<IDiscussion>(`/discussions/${discussionID}`, body);
        return result.data;
    },

    putReaction: async (commentID: RecordID, apiParams: DiscussionsApi.PutReactionParams) => {
        const result = await apiv2.put<IDiscussion[]>(`/comments/${commentID}/reactions`, apiParams);
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
};
