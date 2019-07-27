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
import moment from "moment";
import { IUserFragment } from "@library/@types/api/users";

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

    /**
     * Sort a list of user suggestions.
     *
     * Priorities
     * [Exact Match]
     * [Users active in the last 90 days, sorted loosely with transliteration]
     * [Users not active in the last 90 days, sorted loosely with transliteration]
     *
     * @param users The users to sort.
     * @param searchName The current search text.
     * @param currentMoment The current time.
     */
    public static sortSuggestions(users: IUserSuggestion[], searchName: string, currentMoment = moment()) {
        const looseCollator = Intl.Collator("en", {
            usage: "sort",
            sensitivity: "variant",
            ignorePunctuation: true,
            numeric: true,
        });

        // Days of recent activity to look at.
        const ACTIVE_THRESHOLD = 90;

        // Fan out into most recently active users & less active users.
        let recentlyActive: IUserSuggestion[] = [];
        let lessActive: IUserSuggestion[] = [];

        const daysAgo90 = currentMoment.subtract(ACTIVE_THRESHOLD, "days");

        for (const user of users) {
            if (!user.dateLastActive) {
                lessActive.push(user);
                continue;
            }

            const lastActiveMoment = moment(user.dateLastActive);
            if (lastActiveMoment.isSameOrAfter(daysAgo90)) {
                recentlyActive.push(user);
            } else {
                lessActive.push(user);
            }
        }

        const sortByName = (userA: IUserSuggestion, userB: IUserSuggestion) =>
            looseCollator.compare(userA.name, userB.name);

        const exactToTheTop = (userA: IUserSuggestion, userB: IUserSuggestion) => {
            const casedSearchName = searchName.toLocaleLowerCase();
            const aCasedName = userA.name.toLocaleLowerCase();
            const bCasedName = userB.name.toLocaleLowerCase();

            //  Return exact matches first.
            if (aCasedName.includes(casedSearchName) && !bCasedName.includes(casedSearchName)) {
                return -1;
            }

            if (bCasedName.includes(casedSearchName) && !aCasedName.includes(casedSearchName)) {
                return 1;
            }

            // Fallback to the collator
            return looseCollator.compare(userA.name, userB.name);
        };

        // Sort each set of users separately.
        recentlyActive.sort(sortByName);
        lessActive.sort(sortByName);

        // Join them back together.
        const allUsers = [...recentlyActive, ...lessActive];

        // Sort exact matches to the top.
        allUsers.sort(exactToTheTop);
        return allUsers;
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
