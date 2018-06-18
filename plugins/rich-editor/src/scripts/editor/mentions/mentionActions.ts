/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { ActionsUnion, createAction } from "@dashboard/state/utility";
import { IMentionUser } from "@dashboard/apiv2";

export const LOAD_USERS_REQUEST = "[mentions] load users request";
export const LOAD_USERS_FAILURE = "[mentions] load users failure";
export const LOAD_USERS_SUCCESS = "[mentions] load users success";

export const actions = {
    loadUsersRequest: (username: string) => createAction(LOAD_USERS_REQUEST, username),
    loadUsersFailure: (error: Error) => createAction(LOAD_USERS_FAILURE, error),
    loadUsersSuccess: (users: IMentionUser[]) => createAction(LOAD_USERS_SUCCESS, users),
};

export type ActionTypes = ActionsUnion<typeof actions>;
