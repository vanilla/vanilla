/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUsersStoreState } from "@library/features/users/userTypes";
import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { t } from "@library/utility/appUtils";
import React from "react";
import { connect } from "react-redux";
import { Icon } from "@vanilla/icons";

function NotificationsCount(props: IProps) {
    const { count, open, compact } = props;

    return (
        <MeBoxIcon count={count} countLabel={t("Notifications") + ": "} compact={compact}>
            {open ? <Icon icon="me-notifications-solid" /> : <Icon icon={"me-notifications"} />}
        </MeBoxIcon>
    );
}

interface IOwnProps {
    open?: boolean;
    compact: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps>;

function mapStateToProps(state: IUsersStoreState) {
    const { current } = state.users;
    return {
        count: current.data ? current.data.countUnreadNotifications : 0,
    };
}

export default connect(mapStateToProps)(NotificationsCount);
