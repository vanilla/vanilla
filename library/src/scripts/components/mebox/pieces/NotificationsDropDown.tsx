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
import classNames from "classnames";
import NotificationsContents, { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import NotificationsToggle from "@library/components/mebox/pieces/NotificationsToggle";

interface IProps extends INotificationsProps {
    className?: string;
    buttonClassName?: string;
    contentsClassName?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default class NotificationsDropDown extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("notificationsDropDown");

    public state = {
        open: false,
    };

    public render() {
        const count = this.props.count ? this.props.count : 0;
        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={classNames("vanillaHeader-notifications", this.props.buttonClassName)}
                buttonBaseClass={ButtonBaseClass.CUSTOM}
                renderLeft={true}
                contentsClassName={this.props.contentsClassName}
                buttonContents={
                    <NotificationsToggle
                        count={this.props.count}
                        open={this.state.open}
                        countClass={this.props.countClass}
                    />
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
