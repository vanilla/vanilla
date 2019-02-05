/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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
import { connect } from "react-redux";
import { IMeBoxNotificationItem, MeBoxItemType } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import apiv2 from "@library/apiv2";
import NotificationsActions from "@library/notifications/NotificationsActions";
import { INotificationsStoreState } from "@library/notifications/NotificationsModel";
import get from "lodash/get";
import { INotification } from "@library/@types/api";

interface IProps extends INotificationsProps {
    buttonClassName?: string;
    className?: string;
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
                contentsClassName={this.props.contentsClassName}
                buttonContents={
                    <NotificationsToggle
                        count={this.props.countUnread}
                        open={this.state.open}
                        countClass={this.props.countClass}
                    />
                }
                onVisibilityChange={this.setOpen}
            >
                <NotificationsContents
                    countClass={this.props.countClass}
                    preferencesUrl={`/profile/preferences/${userSlug}`}
                />
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
