/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import NotificationsContents from "@library/headers/mebox/pieces/NotificationsContents";
import type NotificationsContentsImpl from "@library/headers/mebox/pieces/NotificationsContentsImpl";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import React from "react";

interface IProps extends React.ComponentProps<typeof NotificationsContentsImpl> {
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
        const classesHeader = titleBarClasses();
        return (
            <DropDown
                contentID={this.id + "-content"}
                handleID={this.id + "-handle"}
                name={t("Notifications")}
                renderLeft={true}
                buttonClassName={classesHeader.button}
                contentsClassName={classesHeader.dropDownContents}
                buttonContents={<NotificationsCount open={this.state.open} compact={false} />}
                onVisibilityChange={this.setOpen}
                flyoutType={FlyoutType.FRAME}
                onHover={NotificationsContents.preload}
            >
                <NotificationsContents userSlug={userSlug} />
            </DropDown>
        );
    }

    /**
     * Assign the open (visibile) state of this component.
     *
     * @param open Is this menu open and visible?
     */
    private setOpen = (open) => {
        this.setState({
            open,
        });
    };
}
