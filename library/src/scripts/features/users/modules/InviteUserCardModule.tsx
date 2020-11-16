/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import InviteUserCard from "@library/features/users/ui/InviteUserCard";
import { useInviteUsers } from "@library/features/users/userHooks";

export interface IInviteUserCardModule {
    userID: number;
    groupID: number;
    visible: boolean;
}

export function InViteUserCardModule(props: IInviteUserCardModule) {
    const { userID, groupID, visible } = props;

    const { emailsString, updateStoreEmails, invitees, updateStoreInvitees, sentInvitations } = useInviteUsers({
        userID,
        groupID,
    });

    const [isVisible, setIsVisible] = useState(visible);
    const closeModal = () => setIsVisible(false);

    return (
        <>
            <InviteUserCard
                inputEmails={emailsString}
                updateStoreEmails={updateStoreEmails}
                defaultUsers={invitees}
                updateStoreInvitees={updateStoreInvitees}
                visible={isVisible}
                closeModal={closeModal}
                sentInvitations={sentInvitations}
            />
        </>
    );
}
