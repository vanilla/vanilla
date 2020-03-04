/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { logError } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { IRolesStoreState } from "@library/features/users/suggestion/RoleSuggestionModel";
import ReduxActions, { ActionsUnion } from "@library/redux/ReduxActions";
import { IRoleSuggestion } from "@library/features/users/suggestion/IRoleSuggestion";
import RoleSuggestionModel from "@library/features/users/suggestion/RoleSuggestionModel";
import { Dispatch } from "redux";
import apiv2 from "@library/apiv2";
import debounce from "lodash/debounce";

interface ILookupRoleOptions {
    rolename: string;
}

export default class RoleSuggestionActions extends ReduxActions {
    public static readonly SET_ACTIVE_SUGGESTION = "@@roles/SET_ACTIVE_SUGGESTION";
    public static readonly LOAD_ROLES_REQUEST = "@@roles/GET_ROLES_REQUEST";
    public static readonly LOAD_ROLES_RESPONSE = "@@roles/LOAD_ROLES_RESPONSE";
    public static readonly LOAD_ROLES_ERROR = "@@roles/LOAD_ROLES_ERROR";

    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof RoleSuggestionActions.loadRolesACs>
        | ReturnType<typeof RoleSuggestionActions.setActiveAC>;

    // Action creators
    public static loadRolesACs = ReduxActions.generateApiActionCreators(
        RoleSuggestionActions.LOAD_ROLES_REQUEST,
        RoleSuggestionActions.LOAD_ROLES_RESPONSE,
        RoleSuggestionActions.LOAD_ROLES_ERROR,
        {} as IRoleSuggestion[],
        {} as ILookupRoleOptions,
    );

    public static setActiveAC(suggestionID: string, suggestionIndex: number) {
        return ReduxActions.createAction(RoleSuggestionActions.SET_ACTIVE_SUGGESTION, {
            suggestionID,
            suggestionIndex,
        });
    }

    /**
     * Filter roles down to a list that loosely matches the current searchName
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Collator
     */
    public static filterSuggestions(roles: IRoleSuggestion[], searchName: string) {
        const searchCollator = Intl.Collator("en", {
            usage: "search",
            sensitivity: "base",
            ignorePunctuation: true,
            numeric: true,
        });

        return roles.filter((roleSuggestion: IRoleSuggestion) => {
            if (roleSuggestion.name.length < searchName.length) {
                return false;
            }

            const suggestionIDPartial = roleSuggestion.name.substring(0, searchName.length);
            return searchCollator.compare(suggestionIDPartial, searchName) === 0;
        });
    }

    public setActive = this.bindDispatch(RoleSuggestionActions.setActiveAC);

    /**
     * Make an API request for mention suggestions. These results are cached by the lookup rolename.
     */
    private interalLoadRoles = (rolename: string) => {
        return this.dispatch((dispatch: Dispatch<any>, getState: () => IRolesStoreState) => {
            const trie = RoleSuggestionModel.selectSuggestionsTrie(getState());
            // Attempt an exact lookup first.
            const exactLookup = trie.getValue(rolename);
            if (exactLookup != null) {
                switch (exactLookup.status) {
                    case LoadStatus.SUCCESS:
                        if (exactLookup.data) {
                            return dispatch(
                                RoleSuggestionActions.loadRolesACs.response(
                                    { data: exactLookup.data, status: 200 },
                                    { rolename },
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
                            return dispatch(RoleSuggestionActions.loadRolesACs.error(exactLookup.error, { rolename }));
                        }
                }
            }

            // Attempt a partial lookup to try and see if we can get results without an API request
            const partialLookup = trie.getValueFromPartialsOfWord(rolename);
            if (partialLookup != null) {
                switch (partialLookup.status) {
                    case LoadStatus.SUCCESS: {
                        if (partialLookup.data) {
                            // The previous match already found the maximum amount of roles that the server had
                            // Return the previous results.
                            return dispatch(
                                RoleSuggestionActions.loadRolesACs.response(
                                    {
                                        data: RoleSuggestionActions.filterSuggestions(partialLookup.data, rolename),
                                        status: 200,
                                    },
                                    { rolename },
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
            dispatch(RoleSuggestionActions.loadRolesACs.request({ rolename }));

            return apiv2
                .get("/roles")
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
                    dispatch(RoleSuggestionActions.loadRolesACs.response(response, { rolename }));
                })
                .catch(error => {
                    logError(error);
                    dispatch(RoleSuggestionActions.loadRolesACs.error(error, { rolename }));
                });
        });
    };

    public loadRoles = debounce(this.interalLoadRoles, 50);
}
