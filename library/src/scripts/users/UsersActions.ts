/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { IMe } from "@dashboard/@types/api";
import apiv2 from "@library/apiv2";

export interface IInjectableUsersActions {
    usersActions: UsersActions;
}

export default class UsersActions extends ReduxActions {
    public static readonly GET_ME_REQUEST = "@@users/GET_ME_REQUEST";
    public static readonly GET_ME_RESPONSE = "@@users/GET_ME_RESPONSE";
    public static readonly GET_ME_ERROR = "@@users/GET_ME_ERROR";

    public static readonly ACTION_TYPES: ActionsUnion<typeof UsersActions.getMeACs>;

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
