/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import InviteUserCard from "@library/features/users/ui/InviteUserCard";
import { useInviteUsers } from "@library/features/users/userHooks";
import { useUserActions } from "@library/features/users/UserActions";

export interface IInviteUserCardModule {
    userID: number;
    groupID: number;
    visible: boolean;
}

export function InViteUserCardModule(props: IInviteUserCardModule) {
    const { userID, groupID, visible } = props;

    const [isVisible, setIsVisible] = useState(visible);
    const closeModal = () => setIsVisible(false);

    const { clearInviteUsers } = useUserActions();
    const { emailsString, updateStoreEmails, invitees, updateStoreInvitees, sentInvitations, errors } = useInviteUsers({
        userID,
        groupID,
        onSuccess: closeModal,
    });

    return (
        <>
            <InviteUserCard
                inputEmails={emailsString}
                updateStoreEmails={updateStoreEmails}
                defaultUsers={invitees}
                updateStoreInvitees={updateStoreInvitees}
                visible={isVisible}
                closeModal={() => {
                    closeModal();
                    clearInviteUsers({ userID });
                }}
                sentInvitations={sentInvitations}
                errors={errors}
            />
        </>
    );
}
