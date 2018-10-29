/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";
import { IMe } from "@library/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import UsersActions from "@library/users/UsersActions";

export interface IInjectableUserState {
    currentUser: ILoadable<IMe>;
}

interface IUsersState {
    current: ILoadable<IMe>;
}

export interface IUsersStoreState {
    users: IUsersState;
}

/**
 * Reducer and state mapping for users data.
 */
export default class UsersModel implements ReduxReducer<IUsersState> {
    public static readonly GUEST_ID = 0;

    /**
     * Map the current user data out into react props.
     * @param state
     */
    public static mapStateToProps(state: IUsersStoreState): IInjectableUserState {
        if (!("users" in state)) {
            throw new Error(
                `It seems you did not initialize the users model correctly. Could not find "users" in state: ${state}`,
            );
        }

        return {
            currentUser: state.users.current,
        };
    }

    /**
     * @inheritdoc
     */
    public readonly initialState: IUsersState = {
        current: {
            status: LoadStatus.PENDING,
        },
    };

    /**
     * @inheritdoc
     */
    public reducer = (
        state: IUsersState = this.initialState,
        action: typeof UsersActions.ACTION_TYPES,
    ): IUsersState => {
        return produce(state, draft => {
            switch (action.type) {
                case UsersActions.GET_ME_REQUEST:
                    draft.current.status = LoadStatus.LOADING;
                    break;
                case UsersActions.GET_ME_RESPONSE:
                    draft.current.status = LoadStatus.SUCCESS;
                    draft.current.data = action.payload.data;
                    break;
                case UsersActions.GET_ME_ERROR:
                    draft.current.status = LoadStatus.ERROR;
                    draft.current.error = action.payload;
                    break;
            }
        });
    };
}
