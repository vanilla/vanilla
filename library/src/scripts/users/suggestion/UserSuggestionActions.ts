/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Dispatch } from "redux";
import { ActionsUnion, createAction } from "@library/state/utility";
import api from "@library/apiv2";
import { IStoreState } from "@rich-editor/@types/store";
import { IApiError, LoadStatus } from "@library/@types/api";
import { IUserSuggestion } from "@library/users/suggestion/IUserSuggestion";
import ReduxActions from "@library/state/ReduxActions";

interface ILookupUserOptions {
    username: string;
}

export default class UserSuggestionActions extends ReduxActions {
    public static readonly SET_ACTIVE_SUGGESTION = "@@mentions/SET_ACTIVE_SUGGESTION";
    public static readonly LOAD_USERS_REQUEST = "@@mentions/GET_USERS_REQUEST";
    public static readonly LOAD_USERS_FAILURE = "@@mentions/LOAD_USERS_FAILURE";
    public static readonly LOAD_USERS_RESPONSE = "@@mentions/LOAD_USERS_RESPONSE";

    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof UserSuggestionActions.loadUsersACs>
        | ReturnType<typeof UserSuggestionActions.setActiveAC>;

    // The number of characters that we will lookup to try and invalidate a lookup early.
    private static USER_LIMIT = 50;

    // Action creators
    public static loadUsersACs = ReduxActions.generateApiActionCreators(
        UserSuggestionActions.LOAD_USERS_REQUEST,
        UserSuggestionActions.LOAD_USERS_RESPONSE,
        UserSuggestionActions.LOAD_USERS_FAILURE,
        {} as IUserSuggestion[],
        {} as ILookupUserOptions,
    );

    public static setActiveAC(suggestionID: string, suggestionIndex: number) {
        return ReduxActions.createAction(UserSuggestionActions.SET_ACTIVE_SUGGESTION, {
            suggestionID,
            suggestionIndex,
        });
    }

    /**
     * Filter users down to a list that loosely matches the current searchName
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Collator
     */
    public static filterSuggestions(users: IUserSuggestion[], searchName: string) {
        const searchCollator = Intl.Collator("en", {
            usage: "search",
            sensitivity: "base",
            ignorePunctuation: true,
            numeric: true,
        });

        return users.filter((userSuggestion: IUserSuggestion) => {
            if (userSuggestion.name.length < searchName.length) {
                return false;
            }

            const suggestionIDPartial = userSuggestion.name.substring(0, searchName.length);
            return searchCollator.compare(suggestionIDPartial, searchName) === 0;
        });
    }

    /**
     * Make an API request for mention suggestions. These results are cached by the lookup username.
     */
    public loadUsers(username: string) {
        return (dispatch: Dispatch<any>, getState: () => IStoreState) => {
            const { usersTrie } = getState().editor.mentions;

            // Attempt an exact lookup first.
            const exactLookup = usersTrie.getValue(username);
            if (exactLookup != null) {
                switch (exactLookup.status) {
                    case LoadStatus.SUCCESS:
                        if (exactLookup.data) {
                            return dispatch(
                                UserSuggestionActions.loadUsersACs.response(exactLookup.data, { username }),
                            );
                        }
                    case LoadStatus.LOADING:
                        // Already handled
                        return;
                    case LoadStatus.ERROR:
                        // Previously failed.
                        if (exactLookup.error) {
                            return dispatch(UserSuggestionActions.loadUsersACs.error(exactLookup.error!, { username }));
                        }
                }
            }

            // Attempt a partial lookup to try and see if we can get results without an API request
            const partialLookup = usersTrie.getValueFromPartialsOfWord(username);
            if (partialLookup != null) {
                switch (partialLookup.status) {
                    case LoadStatus.SUCCESS: {
                        if (partialLookup.data && partialLookup.data.length < UserSuggestionActions.USER_LIMIT) {
                            // The previous match already found the maximum amount of users that the server had
                            // Return the previous results.
                            return dispatch(
                                UserSuggestionActions.loadUsersACs.response(
                                    {
                                        data: UserSuggestionActions.filterSuggestions(partialLookup.data, username),
                                        status: 200,
                                    },
                                    { username },
                                ),
                            );
                        }
                    }
                    case LoadStatus.ERROR:
                    // Previously failed. We still want to proceed to a real lookup so do nothing.
                    case LoadStatus.PENDING:
                    // We still want to proceed to a real lookup so do nothing.
                }
            }

            // Start the lookup.
            dispatch(UserSuggestionActions.loadUsersACs.request({ username }));

            const params = {
                name: username + "*",
                order: "mention",
                limit: UserSuggestionActions.USER_LIMIT,
            };
            return api
                .get("/users/by-names/", { params /*, cancelToken: this.apiCancelSource.token*/ })
                .then(response => {
                    if (response.status >= 500) {
                        throw new Error(response.data);
                    }

                    // Add unique domIDs to each user.
                    const users = response.data.map(data => {
                        data.domID = "mentionSuggestion" + data.userID;
                        return data;
                    });

                    // Result is good. Lets GO!
                    dispatch(UserSuggestionActions.loadUsersACs.response(users, { username }));
                })
                .catch(error => {
                    dispatch(UserSuggestionActions.loadUsersACs.error(error, { username }));
                });
        };
    }
}
