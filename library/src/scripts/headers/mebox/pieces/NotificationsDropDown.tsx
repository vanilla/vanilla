/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { vanillaHeaderClasses } from "@library/headers/vanillaHeaderStyles";
import NotificationsContents, { INotificationsProps } from "@library/headers/mebox/pieces/NotificationsContents";
import { t } from "@library/utility/appUtils";
import NotificationsCounter from "@library/headers/mebox/pieces/NotificationsCounter";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import DropDown from "@library/flyouts/DropDown";
import classNames from "classnames";

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
        const classesHeader = vanillaHeaderClasses();

        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={classNames("vanillaHeader-notifications", this.props.buttonClassName)}
                buttonBaseClass={ButtonTypes.CUSTOM}
                renderLeft={true}
                toggleButtonClassName="vanillaHeader-button"
                contentsClassName={classNames(this.props.contentsClassName, classesHeader.dropDownContents)}
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
