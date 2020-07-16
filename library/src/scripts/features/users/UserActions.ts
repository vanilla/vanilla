/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError, LoadStatus } from "@library/@types/api/core";
import { IMe, IMeCounts, IUser } from "@library/@types/api/users";
import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IPermission, IPermissions } from "@library/features/users/userModel";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { useSelector } from "react-redux";

const createAction = actionCreatorFactory("@@users");

export interface IGetUserByIDQuery {
    userID: number;
}

// The duration we wait to check for new counts.
const COUNT_CACHE_PERIOD = 60; // 60 Seconds

/**
 * Redux actions for the users data.
 */
export default class UserActions extends ReduxActions {
    public static getMeACs = createAction.async<{}, IMe, IApiError>("GET_ME");
    /**
     * Request the currently signed in user data if it's not loaded.
     */
    public getMe = () => {
        const currentUser = this.getState().users.current;
        if (currentUser.status === LoadStatus.LOADING) {
            // Don't request the user more than once.
            return;
        }
        const apiThunk = bindThunkAction(UserActions.getMeACs, async () => {
            const response = await this.api.get("/users/me");
            return response.data;
        })();

        return this.dispatch(apiThunk);
    };

    public static getPermissionsACs = createAction.async<{}, IPermissions, IApiError>("GET_PERMISSIONS");
    /**
     * Request the currently signed in user data if it's not loaded.
     */
    public getPermissions = () => {
        const permissions = this.getState().users.permissions;
        if (permissions.status === LoadStatus.LOADING) {
            // Don't request the user more than once.
            return;
        }
        const apiThunk = bindThunkAction(UserActions.getPermissionsACs, async () => {
            const response = await this.api.get("/users/$me/permissions");
            return response.data;
        })();

        return this.dispatch(apiThunk);
    };

    public static getCountsACs = createAction.async<{}, { counts: IMeCounts }, IApiError>("GET_ME_COUNTS");

    /**
     * Check if we have valid count data and request it again if it is stale.
     */
    public checkCountData = () => {
        const currentTime = new Date().getTime();
        const { lastRequested } = this.getState().users.countInformation;

        if (lastRequested !== null && currentTime < lastRequested + COUNT_CACHE_PERIOD) {
            // Bailout if we've requested this data within the previous cache period
            return;
        }
        const apiThunk = bindThunkAction(UserActions.getCountsACs, async () => {
            const response = await this.api.get("/users/me-counts");
            return response.data;
        })();

        return this.dispatch(apiThunk);
    };

    public static getUserACs = createAction.async<{ userID: number }, IUser, IApiError>("GET_USER");

    public getUserByID = (query: IGetUserByIDQuery) => {
        const { userID } = query;
        const thunk = bindThunkAction(UserActions.getUserACs, async () => {
            const reponse = await this.api.get(`/users/${userID}/?expand[]=all`);
            return reponse.data;
        })({ userID });
        return this.dispatch(thunk);
    };
}

export function useUserActions() {
    return useReduxActions(UserActions);
}
