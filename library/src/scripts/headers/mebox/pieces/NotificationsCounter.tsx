/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { IUsersStoreState } from "@library/features/users/UsersModel";
import Count from "@library/content/Count";
import { notifications } from "@library/icons/header";
import classNames from "classnames";
import { connect } from "react-redux";

/**
 * Implements Notifications toggle contents
 */
export class NotificationsCounter extends React.PureComponent<IProps> {
    public render() {
        const { count, open, className, countClass } = this.props;
        return (
            <div className={classNames("notificationsToggle", className)}>
                {notifications(!!open)}
                {count > 0 && (
                    <Count
                        className={classNames("vanillaHeader-count", countClass)}
                        label={t("Notifications: ")}
                        count={count}
                    />
                )}
            </div>
        );
    }
}

interface IOwnProps {
    open?: boolean;
    countClass?: string;
    className?: string;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps>;

function mapStateToProps(state: IUsersStoreState) {
    const { current } = state.users;
    return {
        count: current.data ? current.data.countUnreadNotifications : 0,
    };
}

export default connect(mapStateToProps)(NotificationsCounter);
