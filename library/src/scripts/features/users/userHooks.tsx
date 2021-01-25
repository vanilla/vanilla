/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSelector } from "react-redux";
import { IGetUserByIDQuery, IInviteUsersByGroupIDQuery, useUserActions } from "@library/features/users/UserActions";
import { IUsersStoreState } from "@library/features/users/userTypes";
import { useEffect } from "react";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";

export function useUser(query: IGetUserByIDQuery) {
    const actions = useUserActions();
    const { userID } = query;

    const existingResult = useSelector((state: IUsersStoreState) => {
        return (
            state.users.usersByID[userID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = existingResult;

    useEffect(() => {
        if (LoadStatus.PENDING.includes(status)) {
            actions.getUserByID(query);
        }
    }, [status, actions, query]);

    return existingResult;
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
        return state.users.usersInvitationsByID[userID]?.results?.error?.response?.data?.errors;
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
