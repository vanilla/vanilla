/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as mentionActions from "./mentionActions";
import MentionTrie from "./MentionTrie";
import apiv2 from "@dashboard/apiv2";

export interface IMentionState {
    users: MentionTrie;
}

export const initialState: IMentionState = {
    users: new MentionTrie(),
};

export default function mentionReducer(state = initialState, action: mentionActions.ActionTypes): IMentionState {
    switch (action.type) {
        case mentionActions.LOAD_USERS_REQUEST:
            const username = action.payload;
            state.users.insert(username, {
                status: "PENDING",
            });
            return state;
        case mentionActions.LOAD_USERS_FAILURE:
        case mentionActions.LOAD_USERS_SUCCESS:
        default:
            return state;
    }
}
