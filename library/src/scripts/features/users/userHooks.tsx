/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSelector } from "react-redux";
import {
    IGetUserByIDQuery,
    IInviteUsersByGroupIDQuery,
    IPostUserParams,
    IPatchUserParams,
    useUserActions,
} from "@library/features/users/UserActions";
import { IUsersStoreState } from "@library/features/users/userTypes";
import { useCallback, useContext, useDebugValue, useEffect } from "react";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { GUEST_USER_ID } from "@library/features/users/userModel";
import { IMe, IUser, IUserFragment } from "@library/@types/api/users";
import { useUniqueID } from "@library/utility/idUtils";
import React from "react";

interface ICurrentUserContextValue {
    currentUser: IMe | undefined;
}

const CurrentUserContext = React.createContext<ICurrentUserContextValue>({
    currentUser: undefined,
});

export function useCurrentUserContext(): ICurrentUserContextValue {
    return useContext(CurrentUserContext);
}

/**
 * Get the current sessioned user. If the user is a guest, a guest fragment will be returned.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function useCurrentUser(): IMe {
    const context = useCurrentUserContext();
    return context.currentUser!;
}

export function useCurrentUserID(): IUser["userID"] | undefined {
    const currentUser = useCurrentUser();
    return currentUser?.userID;
}

/**
 * Return if the current user is a signed in.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function useCurrentUserSignedIn(): boolean {
    const currentUser = useCurrentUser();
    return currentUser?.userID !== GUEST_USER_ID;
}

export function CurrentUserContextProvider(props: { currentUser?: IMe; children: React.ReactNode }) {
    const { currentUser, children } = props;
    const user: IMe | undefined = currentUser
        ? {
              ...currentUser,
              countUnreadNotifications:
                  "countUnreadNotifications" in currentUser ? currentUser.countUnreadNotifications : 0,
              countUnreadConversations:
                  "countUnreadConversations" in currentUser ? currentUser.countUnreadConversations : 0,
              isAdmin: "isAdmin" in currentUser ? currentUser.isAdmin : false,
              emailConfirmed: "emailConfirmed" in currentUser ? currentUser.emailConfirmed : false,
          }
        : undefined;
    return <CurrentUserContext.Provider value={{ currentUser: user }}>{children}</CurrentUserContext.Provider>;
}

export function ReduxCurrentUserContextProvider(props: { children: React.ReactNode }) {
    const { children } = props;

    const currentUser = useSelector((state: ICoreStoreState) => {
        return state.users.current.data;
    });

    return <CurrentUserContextProvider currentUser={currentUser}>{children}</CurrentUserContextProvider>;
}

export function useUser(query: Partial<IGetUserByIDQuery>): ILoadable<IUser> {
    const actions = useUserActions();
    const { userID } = query;

    const existingResult = useSelector((state: IUsersStoreState) => {
        const pending = {
            status: LoadStatus.PENDING,
        };
        if (userID == null) {
            return pending;
        }
        return state.users.usersByID[userID] ?? pending;
    });

    const { status } = existingResult;

    useEffect(() => {
        if (userID == null) {
            // Nothing to do. We weren't given a userID.
            return;
        }
        if ([LoadStatus.PENDING].includes(status)) {
            actions.getUserByID({ userID });
        }
    }, [status, actions, userID]);

    useDebugValue(existingResult);

    return existingResult;
}

export function usePostUser() {
    const actions = useUserActions();

    async function postUser(params: IPostUserParams) {
        const userData = await actions.postUser(params);
    }

    return postUser;
}

function getEmails(emails: string): string[] {
    if (!emails.trim()) return [];

    // Making it a little more robust by allowing space-separated emails as well
    return emails
        .split(",")
        .join(" ")
        .split(" ")
        .filter((email) => email);
}

function getInviteeIDs(invitees: IComboBoxOption[]): number[] {
    return invitees.map((invitee) => invitee.value as number);
}

export function useInviteUsers(params: { userID: number; groupID: number; onSuccess: () => void }) {
    const { userID, groupID, onSuccess } = params;
    const { inviteUsersByGroupID, updateInvitees, updateEmailsString, clearInviteUsers } = useUserActions();

    const emailsString = useSelector((state: IUsersStoreState) => {
        return state.users.usersInvitationsByID[userID]?.emailsString ?? "";
    });
    const updateStoreEmails = (emailsString) => {
        updateEmailsString({ userID, emailsString });
    };

    const invitees = useSelector((state: IUsersStoreState) => {
        return state.users.usersInvitationsByID[userID]?.invitees ?? [];
    });
    const updateStoreInvitees = (invitees: IComboBoxOption[]) => {
        updateInvitees({ userID, invitees });
    };

    const sentInvitations = () => {
        const userIDs: number[] = getInviteeIDs(invitees);
        const emails: string[] = getEmails(emailsString);

        if (userIDs.length === 0 && emails.length === 0) return;

        const query: IInviteUsersByGroupIDQuery = {
            userID,
            groupID,
            userIDs,
            emails,
        };
        return inviteUsersByGroupID(query);
    };

    const errors = useSelector((state: IUsersStoreState) => {
        return state.users.usersInvitationsByID[userID]?.results?.error;
    });

    const status = useSelector((state: IUsersStoreState) => {
        return state.users.usersInvitationsByID[userID]?.results?.status ?? LoadStatus.PENDING;
    });

    useEffect(() => {
        if (status === LoadStatus.SUCCESS) {
            onSuccess();
            clearInviteUsers({ userID });
        }
    });

    return { emailsString, updateStoreEmails, invitees, updateStoreInvitees, sentInvitations, errors };
}

export function usePatchUser(userID: IUser["userID"]) {
    const userActions = useUserActions();
    const patchID = useUniqueID("userPatch");

    const patchStatus = useSelector((state: IUsersStoreState) => {
        return state.users.patchStatusByPatchID[`${userID}-${patchID}`]?.status ?? LoadStatus.PENDING;
    });

    const patchErrors = useSelector(
        (state: IUsersStoreState) => state.users.patchStatusByPatchID[`${userID}-${patchID}`]?.error,
    );

    const patchUser = useCallback(
        async (params: IPatchUserParams) => {
            return await userActions.patchUser({ ...params, patchID });
        },
        [userActions, patchID],
    );

    return {
        patchUser,
        patchErrors,
        patchStatus,
    };
}
