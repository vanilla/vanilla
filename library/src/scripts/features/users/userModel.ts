/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IMe, IMeCounts, IUser, IUserFragment } from "@library/@types/api/users";
import UserSuggestionModel, { IUserSuggestionState } from "@library/features/users/suggestion/UserSuggestionModel";
import UserActions from "@library/features/users/UserActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import NotificationsActions from "@library/features/notifications/NotificationsActions";
import { IThemeState } from "@library/theming/themeReducer";
import { ILocaleState } from "@library/locales/localeReducer";
import { useSelector } from "react-redux";

export interface IInjectableUserState {
    currentUser: ILoadable<IMe>;
}

interface IUsersState {
    current: ILoadable<IMe>;
    countInformation: {
        counts: IMeCounts;
        lastRequested: number | null; // A timestamp of the last time we received this count data.
    };
    suggestions: IUserSuggestionState;
}

export interface IUsersStoreState {
    users: IUsersState;
}

const suggestionReducer = new UserSuggestionModel().reducer;

export const INITIAL_USERS_STATE: IUsersState = {
    current: {
        status: LoadStatus.PENDING,
    },
    countInformation: {
        counts: [],
        lastRequested: null,
    },
    suggestions: suggestionReducer(undefined, "" as any),
};

export const INITIAL_THEMES_STATE: IThemeState = {
    assets: { status: LoadStatus.PENDING },
};
export const INITIAL_LOCALE_STATE: ILocaleState = {
    locales: { status: LoadStatus.PENDING },
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
        .case(UserActions.getCountsACs.started, state => {
            state.countInformation.lastRequested = new Date().getTime();
            return state;
        })
        .case(UserActions.getCountsACs.done, (state, payload) => {
            state.countInformation.counts = payload.result.counts;
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
