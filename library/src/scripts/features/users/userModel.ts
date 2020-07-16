/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IMe, IMeCounts, IUser, IUserFragment } from "@library/@types/api/users";
import UserSuggestionModel, { IUserSuggestionState } from "@library/features/users/suggestion/UserSuggestionModel";
import UserActions, { useUserActions } from "@library/features/users/UserActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import NotificationsActions from "@library/features/notifications/NotificationsActions";
import { IThemeState } from "@library/theming/themeReducer";
import { ILocaleState } from "@library/locales/localeReducer";
import { useSelector } from "react-redux";
import { useEffect } from "react";

export interface IInjectableUserState {
    currentUser: ILoadable<IMe>;
}

export interface IPermission {
    type: string;
    id: number | null;
    permissions: Record<string, boolean>;
}

export interface IPermissions {
    isAdmin?: boolean;
    permissions: IPermission[];
}

interface IUsersState {
    current: ILoadable<IMe>;
    permissions: ILoadable<IPermissions>;
    countInformation: {
        counts: IMeCounts;
        lastRequested: number | null; // A timestamp of the last time we received this count data.
    };
    suggestions: IUserSuggestionState;
    usersByID: Record<number, ILoadable<IUser>>;
}

export interface IUsersStoreState {
    users: IUsersState;
}

const suggestionReducer = new UserSuggestionModel().reducer;

export const INITIAL_USERS_STATE: IUsersState = {
    current: {
        status: LoadStatus.PENDING,
    },
    permissions: {
        status: LoadStatus.PENDING,
    },
    countInformation: {
        counts: [],
        lastRequested: null,
    },
    suggestions: suggestionReducer(undefined, "" as any),
    usersByID: {},
};

export const GUEST_USER_ID = 0;

/**
 * Determine if a user fragment is a guest.
 */
export function isUserGuest(user: IUserFragment | null | undefined) {
    return user && user.userID === GUEST_USER_ID;
}
/**
 * Reducer for user related data.
 */
export const usersReducer = produce(
    reducerWithInitialState(INITIAL_USERS_STATE)
        .case(UserActions.getMeACs.started, state => {
            state.current.status = LoadStatus.LOADING;
            return state;
        })
        .case(UserActions.getMeACs.done, (state, payload) => {
            state.current.data = payload.result;
            state.current.status = LoadStatus.SUCCESS;
            return state;
        })
        .case(UserActions.getMeACs.failed, (state, payload) => {
            state.current.status = LoadStatus.ERROR;
            state.current.error = payload.error;
            return state;
        })
        .case(UserActions.getPermissionsACs.started, state => {
            state.permissions.status = LoadStatus.LOADING;
            return state;
        })
        .case(UserActions.getPermissionsACs.done, (state, payload) => {
            state.permissions.data = payload.result;
            state.permissions.status = LoadStatus.SUCCESS;
            return state;
        })
        .case(UserActions.getPermissionsACs.failed, (state, payload) => {
            state.permissions.status = LoadStatus.ERROR;
            state.permissions.error = payload.error;
            return state;
        })
        .case(UserActions.getCountsACs.started, state => {
            state.countInformation.lastRequested = new Date().getTime();
            return state;
        })
        .case(UserActions.getCountsACs.done, (state, payload) => {
            state.countInformation.counts = payload.result.counts;
            return state;
        })
        .case(UserActions.getUserACs.started, (state, params) => {
            const { userID } = params;
            state.usersByID[userID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(UserActions.getUserACs.done, (state, payload) => {
            const { userID } = payload.params;
            state.usersByID[userID] = {
                data: payload.result,
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(UserActions.getUserACs.failed, (state, payload) => {
            const { userID } = payload.params;
            state.usersByID[userID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .default((state, action) => {
            if (action.type === NotificationsActions.MARK_ALL_READ_RESPONSE) {
                if (state.current.data) {
                    state.current.data.countUnreadNotifications = 0;
                }
            }
            state.suggestions = suggestionReducer(state.suggestions, action as any);
            return state;
        }),
);

export function mapUsersStoreState(state: ICoreStoreState): IInjectableUserState {
    if (!state.users || !state.users.current) {
        throw new Error(
            `It seems you did not initialize the users model correctly. Could not find "users.current" in state: ${state}`,
        );
    }

    return {
        currentUser: state.users.current,
    };
}

export function useUsersState(): IInjectableUserState {
    return useSelector(mapUsersStoreState);
}

export function usePermissions() {
    const permissions = useSelector((state: ICoreStoreState) => state.users.permissions);
    const { getPermissions } = useUserActions();
    const { status } = permissions;

    useEffect(() => {
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            void getPermissions();
        }
    }, [status, getPermissions]);

    return permissions;
}
