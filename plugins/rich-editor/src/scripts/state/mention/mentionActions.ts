/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { Dispatch } from "redux";
import { ActionsUnion, createAction } from "@dashboard/state/utility";
import api from "@dashboard/apiv2";
import { IMentionSuggestionData } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";
import { IStoreState } from "@rich-editor/@types/store";
import { IApiError, LoadStatus } from "@dashboard/@types/api";

export const SET_ACTIVE_SUGGESTION = "[mentions] set active suggestion";
export const LOAD_USERS_REQUEST = "[mentions] load users request";
export const LOAD_USERS_FAILURE = "[mentions] load users failure";
export const LOAD_USERS_SUCCESS = "[mentions] load users success";

// The number of characters that we will lookup to try and invalidate a lookup early.
const USER_LIMIT = 50;

/**
 * Filter users down to a list that loosely matches the current searchName
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Collator
 */
export function filterSuggestions(users: IMentionSuggestionData[], searchName: string) {
    const searchCollator = Intl.Collator("en", {
        usage: "search",
        sensitivity: "base",
        ignorePunctuation: true,
        numeric: true,
    });

    return users.filter((userSuggestion: IMentionSuggestionData) => {
        if (userSuggestion.name.length < searchName.length) {
            return false;
        }

        const suggestionNamePartial = userSuggestion.name.substring(0, searchName.length);
        return searchCollator.compare(suggestionNamePartial, searchName) === 0;
    });
}

// export function should;

/**
 * Make an API request for mention suggestions. These results are cached by the lookup username.
 */
function loadUsers(username: string) {
    return (dispatch: Dispatch<any>, getState: () => IStoreState) => {
        const { usersTrie } = getState().editor.mentions;

        const thing = true;

        // Attempt an exact lookup first.
        const exactLookup = usersTrie.getValue(username);
        if (exactLookup != null) {
            switch (exactLookup.status) {
                case LoadStatus.SUCCESS:
                case LoadStatus.LOADING:
                    // Already handled
                    return;
                case LoadStatus.ERROR:
                    // Previously failed.
                    return dispatch(actions.loadUsersFailure(username, exactLookup.error));
            }
        }

        // Attempt a partial lookup to try and see if we can get results without an API request
        const partialLookup = usersTrie.getValueFromPartialsOfWord(username);
        if (partialLookup != null) {
            switch (partialLookup.status) {
                case LoadStatus.SUCCESS: {
                    if (partialLookup.data.length < USER_LIMIT) {
                        // The previous match already found the maximum amount of users that the server had
                        // Return the previous results.
                        return dispatch(
                            actions.loadUsersSuccess(username, filterSuggestions(partialLookup.data, username)),
                        );
                    }
                }
                case LoadStatus.ERROR:
                // Previously failed. We still want to proceed to a real lookup so do nothing.
                case LoadStatus.PENDING:
                // We still want to proceed to a real lookup so do nothing.
            }
        }

        // Start the lookup.
        dispatch(actions.loadUsersRequest(username));

        const params = {
            name: username + "*",
            order: "mention",
            limit: USER_LIMIT,
        };
        return api
            .get("/users/by-names/", { params /*, cancelToken: this.apiCancelSource.token*/ })
            .then(response => {
                if (response.status >= 500) {
                    throw new Error(response.data);
                }

                // Add unique domIDs to each user.
                const users = response.data.map(data => {
                    data.domID = "mentionSuggestion" + data.userID;
                    return data;
                });

                // Result is good. Lets GO!
                dispatch(actions.loadUsersSuccess(username, users));
            })
            .catch(error => dispatch(actions.loadUsersFailure(username, error)));
    };
}

export const actions = {
    loadUsersRequest: (username: string) => createAction(LOAD_USERS_REQUEST, { username }),
    loadUsersFailure: (username: string, error: IApiError) => createAction(LOAD_USERS_FAILURE, { username, error }),
    loadUsersSuccess: (username: string, users: IMentionSuggestionData[]) =>
        createAction(LOAD_USERS_SUCCESS, { username, users }),
    setActiveSuggestion: (suggestionID: string, suggestionIndex: number) =>
        createAction(SET_ACTIVE_SUGGESTION, { suggestionID, suggestionIndex }),
};

export const thunks = {
    loadUsers,
};

export type ActionTypes = ActionsUnion<typeof actions>;
