/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@library/apiv2";
import { createAsyncThunk } from "@reduxjs/toolkit";
import { IUser } from "@library/@types/api/users";
import { IReaction } from "@Reactions/types/Reaction";
import { formatUrl } from "@library/utility/appUtils";

export interface IGetUserReactionsParams {
    userID: IUser["userID"];
}

export const getUserReactions = createAsyncThunk<IReaction[], IGetUserReactionsParams>(
    "@@reactions/getUserReactions",
    async (params) => {
        const { userID } = params;
        const { data } = await apiv2.get(`/users/${userID}`, {
            params: {
                expand: ["reactionsReceived"],
            },
        });
        const reactionsByKey: Record<string, IReaction> = data.reactionsReceived;
        const userName = data.name;
        for (const [key, reaction] of Object.entries(reactionsByKey)) {
            reactionsByKey[key].url = formatUrl(
                `/profile/reactions/${encodeURIComponent(userName)}?reaction=${encodeURIComponent(reaction.urlcode)}`,
                true,
            );
        }
        return Object.values(reactionsByKey);
    },
    {},
);
