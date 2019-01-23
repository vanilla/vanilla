/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { IMe } from "@library/@types/api";
import apiv2 from "@library/apiv2";

export interface IInjectableUsersActions {
    usersActions: UsersActions;
}

/**
 * Redux actions for the users data.
 */
export default class UsersActions extends ReduxActions {
    public static readonly GET_ME_REQUEST = "@@users/GET_ME_REQUEST";
    public static readonly GET_ME_RESPONSE = "@@users/GET_ME_RESPONSE";
    public static readonly GET_ME_ERROR = "@@users/GET_ME_ERROR";

    public static readonly ACTION_TYPES: ActionsUnion<typeof UsersActions.getMeACs>;

    /**
     * Map redux's dispatch into a user actions instance prop.
     *
     * @param dispatch The redux dispatch function.
     */
    public static mapDispatch(dispatch): IInjectableUsersActions {
        return {
            usersActions: new UsersActions(dispatch, apiv2),
        };
    }

    public static getMeACs = ReduxActions.generateApiActionCreators(
        UsersActions.GET_ME_REQUEST,
        UsersActions.GET_ME_RESPONSE,
        UsersActions.GET_ME_ERROR,
        {} as IMe,
        {},
    );

    public getMe() {
        return this.dispatchApi("get", "/users/me", UsersActions.getMeACs, {});
    }
}
