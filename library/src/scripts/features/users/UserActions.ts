/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError, LoadStatus } from "@library/@types/api/core";
import { IMe, IMeCounts, IUser, IInvitees } from "@library/@types/api/users";
import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IPermissions } from "@library/features/users/userTypes";
import { IComboBoxOption } from "@library/features/search/SearchBar";

const createAction = actionCreatorFactory("@@users");

export interface IGetUserByIDQuery {
    userID: number;
}

export interface IInviteUsersByGroupIDQuery {
    userID: number;
    groupID: number; // The group to be invited
    userIDs: number[]; // The invitees
    emails: string[]; // The invitees
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

    public static clearInviteUsersAC = createAction<{ userID: number }>("CLEAR_INVITE_USER");
    public clearInviteUsers = this.bindDispatch(UserActions.clearInviteUsersAC);

    public static inviteUsersACs = createAction.async<IInviteUsersByGroupIDQuery, IInvitees[], IApiError>(
        "INVITE_USERS",
    );

    public inviteUsersByGroupID = (query: IInviteUsersByGroupIDQuery) => {
        const { groupID, userIDs, emails } = query;
        const body = { userIDs, emails };
        const thunk = bindThunkAction(UserActions.inviteUsersACs, async () => {
            const response = await this.api.post(`/groups/${groupID}/invitations`, body);
            return response.data;
        })(query);
        return this.dispatch(thunk);
    };

    public static updateInviteeIDsAC = createAction<{ userID: number; IDs: number[] }>("UPDATE_INVITEE_IDS");
    public updateIDs = ({ userID, IDs }) => {
        this.dispatch(UserActions.updateInviteeIDsAC({ userID, IDs }));
    };

    public static updateInviteeEmailsAC = createAction<{ userID: number; emails: string[] }>("UPDATE_INVITEE_EMAIlS");
    public updateEmails = ({ userID, emails }) => {
        this.dispatch(UserActions.updateInviteeEmailsAC({ userID, emails }));
    };

    public static updateInviteeEmailsStringAC = createAction<{ userID: number; emailsString: string }>(
        "UPDATE_INVITEE_EMAIlS_STRING",
    );
    public updateEmailsString = ({ userID, emailsString }) => {
        this.dispatch(UserActions.updateInviteeEmailsStringAC({ userID, emailsString }));
    };

    public static updateInviteesAC = createAction<{ userID: number; invitees: IComboBoxOption[] }>("UPDATE_INVITEES");
    public updateInvitees = ({ userID, invitees }) => {
        this.dispatch(UserActions.updateInviteesAC({ userID, invitees }));
    };
}

export function useUserActions() {
    return useReduxActions(UserActions);
}
