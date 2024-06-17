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

export default function NotificationsCount(props: IProps) {
    const { open, compact } = props;
    const currentUser = useCurrentUser();
    const count = currentUser?.countUnreadNotifications ?? 0;

    return (
        <MeBoxIcon count={count} countLabel={t("Notifications") + ": "} compact={compact}>
            {open ? <Icon icon="me-notifications-solid" /> : <Icon icon={"me-notifications"} />}
        </MeBoxIcon>
    );
}
