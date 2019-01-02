/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { notifications } from "@library/components/icons/header";
import Count from "@library/components/mebox/pieces/Count";
import classNames from "classnames";

interface IProps {
    count?: number;
    open?: boolean;
    countClass?: string;
    className?: string;
}

/**
 * Implements Notifications toggle contents
 */
export default class NotificationsToggle extends React.PureComponent<IProps> {
    public render() {
        const count = this.props.count ? this.props.count : 0;
        return (
            <div className={classNames("meBox-buttonContent", this.props.className)}>
                {notifications(!!this.props.open)}
                {count > 0 && (
                    <Count
                        className={classNames("vanillaHeader-count", this.props.countClass)}
                        label={t("Notifications: ")}
                        count={this.props.count}
                    />
                )}
            </div>
        );
    }
}
