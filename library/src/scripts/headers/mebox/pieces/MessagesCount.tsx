/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { t } from "@library/utility/appUtils";
import React from "react";
import { useUsersState } from "@library/features/users/userModel";
import { Icon } from "@vanilla/icons";

interface IProps {
    open?: boolean;
    compact: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default function MessagesCount(props: IProps) {
    const { open, compact } = props;
    const currentUser = useUsersState().currentUser;
    const count = currentUser.data?.countUnreadConversations ? currentUser.data.countUnreadConversations : 0;

    return (
        <MeBoxIcon count={count} countLabel={t("Messages") + ": "} compact={compact}>
            <Icon icon={open ? "me-messages-solid" : "me-messages"} />
        </MeBoxIcon>
    );
}
