/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import SuggestionTrie from "@library/features/users/suggestion/SuggestionTrie";
import { IRoleSuggestion } from "@library/features/users/suggestion/IRoleSuggestion";
import RoleSuggestionActions from "@library/features/users/suggestion/RoleSuggestionActions";
import ReduxReducer from "@library/redux/ReduxReducer";
import moment from "moment";

interface IRolesState {
    suggestions: IRoleSuggestionState;
}
export interface IRolesStoreState {
    roles: IRolesState;
}
export interface IRoleSuggestionState {
    lastSuccessfulRolename: string | null;
    currentRolename: string | null;
    trie: SuggestionTrie<IRoleSuggestion>;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
}

export interface IInjectableSuggestionsProps {
    lastSuccessfulRolename: string | null;
    currentRolename: string | null;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
    suggestions: ILoadable<IRoleSuggestion[]>;
    isLoading: boolean;
}

export default class RoleSuggestionModel implements ReduxReducer<IRoleSuggestionState> {
    private static readonly defaultSuggestions: ILoadable<IRoleSuggestion[]> = {
        status: LoadStatus.PENDING,
    };

    public static mapStateToProps(state: IRolesStoreState): IInjectableSuggestionsProps {
        const stateSlice = { ...RoleSuggestionModel.stateSlice(state) };
        const { trie, ...rest } = stateSlice;
        const suggestions = stateSlice.lastSuccessfulRolename
            ? trie.getValue(stateSlice.lastSuccessfulRolename) || RoleSuggestionModel.defaultSuggestions
            : RoleSuggestionModel.defaultSuggestions;

        const currentNode = stateSlice.currentRolename && trie.getValue(stateSlice.currentRolename);
        const isLoading = !!currentNode && currentNode.status === LoadStatus.LOADING;

        return {
            ...rest,
            suggestions,
            isLoading,
        };
    }

    public static selectSuggestionsTrie(state: IRolesStoreState): SuggestionTrie<IRoleSuggestion> {
        return RoleSuggestionModel.stateSlice(state).trie;
    }

    private static stateSlice(state: IRolesStoreState): IRoleSuggestionState {
        if (!state.roles || !state.roles.suggestions) {
            throw new Error(
                `Could not find roles.suggestions in state ${state}. Be sure to initialize the rolesReducer()`,
            );
        }

        return state.roles.suggestions;
    }

    /**
     * Sort a list of roles suggestions.
     *
     * @param roles The roles to sort.
     * @param searchName The current search text.
     * @param currentMoment The current time.
     */
    public static sortSuggestions(roles: IRoleSuggestion[], searchName: string, currentMoment = moment()) {
        const looseCollator = Intl.Collator("en", {
            usage: "sort",
            sensitivity: "variant",
            ignorePunctuation: true,
            numeric: true,
        });

        const sortByName = (roleA: IRoleSuggestion, roleB: IRoleSuggestion) => {
            const casedSearchName = searchName.toLocaleLowerCase();
            const aCasedName = roleA.name.toLocaleLowerCase();
            const bCasedName = roleB.name.toLocaleLowerCase();

            // Return partial matches first.
            if (aCasedName.startsWith(casedSearchName) && !bCasedName.startsWith(casedSearchName)) {
                return -1;
            }
            if (bCasedName.startsWith(casedSearchName) && !aCasedName.startsWith(casedSearchName)) {
                return 1;
            }
            return looseCollator.compare(roleA.name.toLocaleLowerCase(), roleB.name.toLocaleLowerCase());
        };

        const exactToTheTop = (roleA: IRoleSuggestion, roleB: IRoleSuggestion) => {
            const casedSearchName = searchName.toLocaleLowerCase();
            const aCasedName = roleA.name.toLocaleLowerCase();

            //  Return exact matches first.
            if (aCasedName === casedSearchName) {
                return -1;
            }

            // Don't affect the sorts otherwise.
            return 0;
        };

        roles.sort(sortByName);

        // Sort exact matches to the top.
        roles.sort(exactToTheTop);
        return roles;
    }

    public readonly initialState: IRoleSuggestionState = {
        lastSuccessfulRolename: null,
        currentRolename: null,
        trie: new SuggestionTrie<IRoleSuggestion>(),
        activeSuggestionID: "",
        activeSuggestionIndex: 0,
    };

    public reducer = (
        state = this.initialState,
        action: typeof RoleSuggestionActions.ACTION_TYPES,
    ): IRoleSuggestionState => {
        switch (action.type) {
            case RoleSuggestionActions.LOAD_ROLES_REQUEST: {
                const { rolename } = action.meta;
                state.trie.insert(rolename, {
                    status: LoadStatus.LOADING,
                });

                // We want to invalidate the previous results unless:
                // - The new string is longer than the old one
                // - The new string is a superset of the old one.
                let shouldKeepPreviousResults = false;
                const previousSuccessfulName = state.lastSuccessfulRolename;
                if (previousSuccessfulName != null && rolename.length >= previousSuccessfulName.length) {
                    const newNameSubstring = rolename.substring(0, previousSuccessfulName.length);
                    if (newNameSubstring === previousSuccessfulName) {
                        shouldKeepPreviousResults = true;
                    }
                }

                return {
                    ...state,
                    currentRolename: rolename,
                    lastSuccessfulRolename: shouldKeepPreviousResults ? state.lastSuccessfulRolename : null,
                };
            }
            case RoleSuggestionActions.LOAD_ROLES_ERROR: {
                const error = action.payload;
                const { rolename } = action.meta;
                state.trie.insert(rolename, {
                    status: LoadStatus.ERROR,
                    data: undefined,
                    error,
                });
                return state;
            }
            case RoleSuggestionActions.LOAD_ROLES_RESPONSE: {
                const roles = action.payload.data;
                const { rolename } = action.meta;
                state.trie.insert(rolename, {
                    status: LoadStatus.SUCCESS,
                    data: RoleSuggestionModel.sortSuggestions(roles, rolename),
                });

                const firstRoleID = roles.length > 0 ? roles[0].domID : "";
                return {
                    ...state,
                    activeSuggestionID: firstRoleID,
                    activeSuggestionIndex: 0,
                    lastSuccessfulRolename: rolename,
                    currentRolename: rolename,
                };
            }
            case RoleSuggestionActions.SET_ACTIVE_SUGGESTION: {
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
