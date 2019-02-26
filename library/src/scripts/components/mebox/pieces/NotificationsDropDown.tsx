/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DropDown from "@library/components/dropdown/DropDown";
import { ButtonBaseClass } from "@library/components/forms/Button";
import NotificationsContents, { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import NotificationsCounter from "@library/components/mebox/pieces/NotificationsCounter";
import classNames from "classnames";
import * as React from "react";

interface IProps extends INotificationsProps {
    buttonClassName?: string;
    className?: string;
    toggleContentClassName?: string;
    contentsClassName?: string;
    countUnread: number;
    userSlug: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Notifications menu for header
 */
export default class NotificationsDropDown extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("notificationsDropDown");

    public state: IState = {
        open: false,
    };

    /**
     * Get the React component to added to the page.
     *
     * @returns A DropDown component, configured to display notifications.
     */
    public render() {
        const { userSlug } = this.props;

        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={classNames("vanillaHeader-notifications", this.props.buttonClassName)}
                buttonBaseClass={ButtonBaseClass.CUSTOM}
                renderLeft={true}
                toggleButtonClassName="vanillaHeader-button"
                contentsClassName={this.props.contentsClassName}
                buttonContents={
                    <NotificationsCounter
                        open={this.state.open}
                        className={this.props.toggleContentClassName}
                        countClass={this.props.countClass}
                    />
                }
                onVisibilityChange={this.setOpen}
            >
                <NotificationsContents countClass={this.props.countClass} userSlug={userSlug} />
            </DropDown>
        );
    }

    /**
     * Assign the open (visibile) state of this component.
     *
     * @param open Is this menu open and visible?
     */
    private setOpen = open => {
        this.setState({
            open,
        });
    };
}
