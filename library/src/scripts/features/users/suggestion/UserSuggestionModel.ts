/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IUsersStoreState } from "@library/features/users/userModel";
import SuggestionTrie from "@library/features/users/suggestion/SuggestionTrie";
import { IUserSuggestion } from "@library/features/users/suggestion/IUserSuggestion";
import UserSuggestionActions from "@library/features/users/suggestion/UserSuggestionActions";
import ReduxReducer from "@library/redux/ReduxReducer";

export interface IUserSuggestionState {
    lastSuccessfulUsername: string | null;
    currentUsername: string | null;
    trie: SuggestionTrie;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
}

export interface IInjectableSuggestionsProps {
    lastSuccessfulUsername: string | null;
    currentUsername: string | null;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
    suggestions: ILoadable<IUserSuggestion[]>;
    isLoading: boolean;
}

export default class UserSuggestionModel implements ReduxReducer<IUserSuggestionState> {
    private static readonly defaultSuggestions: ILoadable<IUserSuggestion[]> = {
        status: LoadStatus.PENDING,
    };

    public static mapStateToProps(state: IUsersStoreState): IInjectableSuggestionsProps {
        const stateSlice = { ...UserSuggestionModel.stateSlice(state) };
        const { trie, ...rest } = stateSlice;
        const suggestions = stateSlice.lastSuccessfulUsername
            ? trie.getValue(stateSlice.lastSuccessfulUsername) || UserSuggestionModel.defaultSuggestions
            : UserSuggestionModel.defaultSuggestions;

        const currentNode = stateSlice.currentUsername && trie.getValue(stateSlice.currentUsername);
        const isLoading = !!currentNode && currentNode.status === LoadStatus.LOADING;

        return {
            ...rest,
            suggestions,
            isLoading,
        };
    }

    public static selectSuggestionsTrie(state: IUsersStoreState): SuggestionTrie {
        return UserSuggestionModel.stateSlice(state).trie;
    }

    private static stateSlice(state: IUsersStoreState): IUserSuggestionState {
        if (!state.users || !state.users.suggestions) {
            throw new Error(
                `Could not find users.suggestions in state ${state}. Be sure to initialize the usersReducer()`,
            );
        }

        return state.users.suggestions;
    }

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
        trie: new SuggestionTrie(),
        activeSuggestionID: "",
        activeSuggestionIndex: 0,
    };

    public reducer = (
        state = this.initialState,
        action: typeof UserSuggestionActions.ACTION_TYPES,
    ): IUserSuggestionState => {
        switch (action.type) {
            case UserSuggestionActions.LOAD_USERS_REQUEST: {
                const { username } = action.meta;
                state.trie.insert(username, {
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
            case UserSuggestionActions.LOAD_USERS_ERROR: {
                const error = action.payload;
                const { username } = action.meta;
                state.trie.insert(username, {
                    status: LoadStatus.ERROR,
                    data: undefined,
                    error,
                });
                return state;
            }
            case UserSuggestionActions.LOAD_USERS_RESPONSE: {
                const users = action.payload.data;
                const { username } = action.meta;
                state.trie.insert(username, {
                    status: LoadStatus.SUCCESS,
                    data: UserSuggestionModel.sortSuggestions(users, username),
                });

                const firstUserID = users.length > 0 ? users[0].domID : "";
                return {
                    ...state,
                    activeSuggestionID: firstUserID,
                    activeSuggestionIndex: 0,
                    lastSuccessfulUsername: username,
                    currentUsername: username,
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
    };
}
