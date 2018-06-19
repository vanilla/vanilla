/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { Dispatch } from "redux";
import { ActionsUnion, createAction } from "@dashboard/state/utility";
import { IMentionUser } from "@dashboard/apiv2";
import api from "@dashboard/apiv2";
import IState from "@rich-editor/state/IState";

export const LOAD_USERS_REQUEST = "[mentions] load users request";
export const LOAD_USERS_FAILURE = "[mentions] load users failure";
export const LOAD_USERS_SUCCESS = "[mentions] load users success";

// The number of characters that we will lookup to try and invalidate a lookup early.
const REASONABLE_INVALIDATION_SIZE = 3;
const USER_LIMIT = 50;

/**
 * Make an API request for mention suggestions. These results are cached by the lookup username.
 */
function loadUsers(username: string) {
    return (dispatch: Dispatch<any>, getState: () => IState) => {
        const { usersTrie } = getState().editor.mentions;

        // Attempt an exact lookup first.
        const exactLookup = usersTrie.getValue(username);
        if (exactLookup != null) {
            switch (exactLookup.status) {
                case "SUCCESSFUL":
                    return dispatch(actions.loadUsersSuccess(username, exactLookup.users));
                case "PENDING":
                    // Already working on it.
                    return;
                case "FAILED":
                    // Previously failed.
                    return dispatch(actions.loadUsersFailure(username, exactLookup.error));
            }
        }

        // Attempt a partial lookup to try and see if there will be no results without an API request.
        if (username.length > REASONABLE_INVALIDATION_SIZE) {
            const partialName = username.substring(0, REASONABLE_INVALIDATION_SIZE);
            const partialLookup = usersTrie.getValue(partialName);

            if (partialLookup != null) {
                switch (partialLookup.status) {
                    case "SUCCESSFUL": {
                        if (partialLookup.users.length < USER_LIMIT) {
                            // The previous match already found the maximum amount of users that the server had
                            // Return the previous results.
                            return dispatch(actions.loadUsersSuccess(username, partialLookup.users));
                        }
                    }
                    case "FAILED":
                    // Previously failed. We still want to proceed to a real lookup so do nothing.
                    case "PENDING":
                    // We still want to proceed to a real lookup so do nothing.
                }
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
            .get("/users/by-names/", { params, cancelToken: this.apiCancelSource.token })
            .then(response => {
                if (response.status >= 500) {
                    throw new Error(response.data);
                }

                // Result is good. Lets GO!
                dispatch(actions.loadUsersSuccess(username, response.data));
            })
            .catch(error => dispatch(actions.loadUsersFailure(username, error)));
    };
}

export const actions = {
    loadUsersRequest: (username: string) => createAction(LOAD_USERS_REQUEST, { username }),
    loadUsersFailure: (username: string, error: Error) => createAction(LOAD_USERS_FAILURE, { username, error }),
    loadUsersSuccess: (username: string, users: IMentionUser[]) =>
        createAction(LOAD_USERS_SUCCESS, { username, users }),
};

export const thunks = {
    loadUsers,
};

export type ActionTypes = ActionsUnion<typeof actions>;
