/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { logError } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { IUsersStoreState } from "@library/features/users/userModel";
import ReduxActions, { ActionsUnion } from "@library/redux/ReduxActions";
import { IUserSuggestion } from "@library/features/users/suggestion/IUserSuggestion";
import UserSuggestionModel from "@library/features/users/suggestion/UserSuggestionModel";
import { Dispatch } from "redux";
import apiv2 from "@library/apiv2";
import debounce from "lodash/debounce";

interface ILookupUserOptions {
    username: string;
}

export default class UserSuggestionActions extends ReduxActions {
    public static readonly SET_ACTIVE_SUGGESTION = "@@mentions/SET_ACTIVE_SUGGESTION";
    public static readonly LOAD_USERS_REQUEST = "@@mentions/GET_USERS_REQUEST";
    public static readonly LOAD_USERS_RESPONSE = "@@mentions/LOAD_USERS_RESPONSE";
    public static readonly LOAD_USERS_ERROR = "@@mentions/LOAD_USERS_ERROR";

    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof UserSuggestionActions.loadUsersACs>
        | ReturnType<typeof UserSuggestionActions.setActiveAC>;

    // The number of characters that we will lookup to try and invalidate a lookup early.
    private static USER_LIMIT = 50;

    // Action creators
    public static loadUsersACs = ReduxActions.generateApiActionCreators(
        UserSuggestionActions.LOAD_USERS_REQUEST,
        UserSuggestionActions.LOAD_USERS_RESPONSE,
        UserSuggestionActions.LOAD_USERS_ERROR,
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

    public setActive = this.bindDispatch(UserSuggestionActions.setActiveAC);

    /**
     * Make an API request for mention suggestions. These results are cached by the lookup username.
     */
    private interalLoadUsers = (username: string) => {
        return this.dispatch((dispatch: Dispatch<any>, getState: () => IUsersStoreState) => {
            const trie = UserSuggestionModel.selectSuggestionsTrie(getState());
            // Attempt an exact lookup first.
            const exactLookup = trie.getValue(username);
            if (exactLookup != null) {
                switch (exactLookup.status) {
                    case LoadStatus.SUCCESS:
                        if (exactLookup.data) {
                            return dispatch(
                                UserSuggestionActions.loadUsersACs.response(
                                    { data: exactLookup.data, status: 200 },
                                    { username },
                                ),
                            );
                        }
                        break;
                    case LoadStatus.LOADING:
                        // Already handled
                        return;
                    case LoadStatus.ERROR:
                        // Previously failed.
                        if (exactLookup.error) {
                            return dispatch(UserSuggestionActions.loadUsersACs.error(exactLookup.error, { username }));
                        }
                }
            }

            // Attempt a partial lookup to try and see if we can get results without an API request
            const partialLookup = trie.getValueFromPartialsOfWord(username);
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
                        break;
                    }
                    case LoadStatus.ERROR:
                        break;
                    // Previously failed. We still want to proceed to a real lookup so do nothing.
                    case LoadStatus.PENDING:
                        break;
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
            return apiv2
                .get("/users/by-names/", { params /*, cancelToken: this.apiCancelSource.token*/ })
                .then(response => {
                    if (response.status >= 500) {
                        throw new Error(response.data);
                    }

                    // Add unique domIDs to each user.
                    response.data = response.data.map(data => {
                        data.domID = "mentionSuggestion" + data.userID;
                        return data;
                    });

                    // Result is good. Lets GO!
                    dispatch(UserSuggestionActions.loadUsersACs.response(response, { username }));
                })
                .catch(error => {
                    logError(error);
                    dispatch(UserSuggestionActions.loadUsersACs.error(error, { username }));
                });
        });
    };

    public loadUsers = debounce(this.interalLoadUsers, 50);
}
