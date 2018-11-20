/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { notifications, settings } from "@library/components/icons/header";
import Count from "@library/components/mebox/pieces/Count";
import classNames from "classnames";
import NotificationsContents, { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";

interface IProps extends INotificationsProps {
    className?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default class NotificationsDropDown extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("notificationsDropDown");

    public constructor(props) {
        super(props);
        this.state = {
            open: false,
        };
    }

    public render() {
        const count = this.props.count ? this.props.count : 0;
        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={"vanillaHeader-notifications meBox-button"}
                buttonBaseClass={ButtonBaseClass.CUSTOM}
                renderLeft={true}
                contentsClassName="meBox-dropDownContents"
                buttonContents={
                    <div className="meBox-buttonContent">
                        {notifications(this.state.open)}
                        <Count
                            className={classNames("vanillaHeader-notificationsCount", this.props.countClass)}
                            label={t("Notifications: ")}
                            count={this.props.count}
                        />
                    </div>
                }
                onVisibilityChange={this.setOpen}
            >
                <NotificationsContents
                    data={this.props.data}
                    count={this.props.count}
                    countClass={this.props.countClass}
                    userSlug={this.props.userSlug}
                />
            </DropDown>
        );
    }

    private setOpen = open => {
        this.setState({
            open,
        });
    };
}
