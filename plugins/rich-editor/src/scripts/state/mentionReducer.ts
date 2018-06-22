/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as mentionActions from "./mentionActions";
import MentionTrie from "@rich-editor/state/MentionTrie";
import { IMentionSuggestionData } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";
import { IMentionState } from "@rich-editor/state/IState";

export const initialState: IMentionState = {
    lastSuccessfulUsername: null,
    currentUsername: null,
    usersTrie: new MentionTrie(),
    activeSuggestionID: "",
    activeSuggestionIndex: 0,
};

export function sortSuggestions(users: IMentionSuggestionData[], searchName: string) {
    const looseCollator = Intl.Collator("en", {
        usage: "sort",
        sensitivity: "variant",
        ignorePunctuation: true,
        numeric: true,
    });

    return users.sort((userA, userB) => {
        //  Return exact matches first.
        if (userA.name.includes(searchName) && !userB.name.includes(searchName)) {
            return -1;
        }

        if (userB.name.includes(searchName) && !userA.name.includes(searchName)) {
            return 1;
        }

        // Then do a loose sort.
        return looseCollator.compare(userA.name, userB.name);
    });
}

export default function mentionReducer(state = initialState, action: mentionActions.ActionTypes): IMentionState {
    switch (action.type) {
        case mentionActions.LOAD_USERS_REQUEST: {
            const { username } = action.payload;
            state.usersTrie.insert(username, {
                status: "PENDING",
            });

            // We want to invalidate the previous results unless:
            // - The new string is longer than the old one
            // - The new string is a superset of the old one.
            let shouldKeepPreviousResults = false;
            const previousSuccessfulName = state.lastSuccessfulUsername;
            if (previousSuccessfulName != null && username.length > previousSuccessfulName.length) {
                const newNameSubstring = username.substring(0, previousSuccessfulName.length);
                if (newNameSubstring === previousSuccessfulName) {
                    shouldKeepPreviousResults = true;
                }
            }

            return {
                ...state,
                currentUsername: username,
                lastSuccessfulUsername: shouldKeepPreviousResults ? state.lastSuccessfulUsername : null,
            };
        }
        case mentionActions.LOAD_USERS_FAILURE: {
            const { username, error } = action.payload;
            state.usersTrie.insert(username, {
                status: "FAILED",
                users: null,
                error,
            });
            return state;
        }
        case mentionActions.LOAD_USERS_SUCCESS: {
            const { username, users } = action.payload;
            state.usersTrie.insert(username, {
                status: "SUCCESSFUL",
                users: sortSuggestions(users, username),
            });

            const firstUserID = users.length > 0 ? users[0].domID : "";
            return {
                ...state,
                activeSuggestionID: firstUserID,
                activeSuggestionIndex: 0,
                lastSuccessfulUsername: username,
                currentUsername: username === state.currentUsername ? null : state.currentUsername,
            };
        }
        case mentionActions.SET_ACTIVE_SUGGESTION: {
            const { suggestionID, suggestionIndex } = action.payload;
            return {
                ...state,
                activeSuggestionID: suggestionID,
                activeSuggestionIndex: suggestionIndex,
            };
        }
        default:
            return state;
    }
}
