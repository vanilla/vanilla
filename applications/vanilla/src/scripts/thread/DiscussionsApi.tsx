/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import apiv2 from "@library/apiv2";
import { RecordID } from "@vanilla/utils";

export const DiscussionsApi = {
    putReaction: async (commentID: RecordID, apiParams: DiscussionsApi.PutReactionParams) => {
        const result = await apiv2.put<IDiscussion[]>(`/comments/${commentID}/reactions`, apiParams);

        return result.data;
    },
};
