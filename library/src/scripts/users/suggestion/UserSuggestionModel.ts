/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api";
import { IUserSuggestion } from "@library/users/suggestion/IUserSuggestion";
import SuggestionTrie from "@library/users/suggestion/SuggestionTrie";
import ReduxReducer from "@library/state/ReduxReducer";
import UserSuggestionActions from "@library/users/suggestion/UserSuggestionActions";

export interface IUserSuggestionState {
    lastSuccessfulUsername: string | null;
    currentUsername: string | null;
    usersTrie: SuggestionTrie;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
}

export default class UserSuggestionModel implements ReduxReducer<IUserSuggestionState> {
    public static sortSuggestions(users: IUserSuggestion[], searchName: string) {
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

    public readonly initialState: IUserSuggestionState = {
        lastSuccessfulUsername: null,
        currentUsername: null,
        usersTrie: new SuggestionTrie(),
        activeSuggestionID: "",
        activeSuggestionIndex: 0,
    };

    public reducer(state = this.initialState, action: typeof UserSuggestionActions.ACTION_TYPES): IUserSuggestionState {
        switch (action.type) {
            case UserSuggestionActions.LOAD_USERS_REQUEST: {
                const { username } = action.meta;
                state.usersTrie.insert(username, {
                    status: LoadStatus.LOADING,
                });

                // We want to invalidate the previous results unless:
                // - The new string is longer than the old one
                // - The new string is a superset of the old one.
                let shouldKeepPreviousResults = false;
                const previousSuccessfulName = state.lastSuccessfulUsername;
                if (previousSuccessfulName != null && username.length >= previousSuccessfulName.length) {
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
            case UserSuggestionActions.LOAD_USERS_FAILURE: {
                const error = action.payload;
                const { username } = action.meta;
                state.usersTrie.insert(username, {
                    status: LoadStatus.ERROR,
                    data: undefined,
                    error,
                });
                return state;
            }
            case UserSuggestionActions.LOAD_USERS_RESPONSE: {
                const users = action.payload.data;
                const { username } = action.meta;
                state.usersTrie.insert(username, {
                    status: LoadStatus.SUCCESS,
                    data: UserSuggestionModel.sortSuggestions(users, username),
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
            case UserSuggestionActions.SET_ACTIVE_SUGGESTION: {
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
}
