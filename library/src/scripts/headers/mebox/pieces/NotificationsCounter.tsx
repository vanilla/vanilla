/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "../../../dom/appUtils";
import { notifications } from "../../../icons/header";
import Count from "library/src/scripts/content/Count";
import classNames from "classnames";
import { connect } from "react-redux";
import { IUsersStoreState } from "../../../features/users/UsersModel";

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
