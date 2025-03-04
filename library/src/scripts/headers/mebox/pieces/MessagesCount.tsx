/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { t } from "@library/utility/appUtils";
import React from "react";
import { Icon } from "@vanilla/icons";
import { useCurrentUser } from "@library/features/users/userHooks";

interface IProps {
    open?: boolean;
    compact: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default function MessagesCount(props: IProps) {
    const { open, compact } = props;
    const currentUser = useCurrentUser();
    const count = currentUser?.countUnreadConversations ?? 0;

    return (
        <MeBoxIcon count={count} countLabel={t("Messages") + ": "} compact={compact}>
            <Icon icon={open ? "me-messages-filled" : "me-messages-empty"} />
        </MeBoxIcon>
    );
}
