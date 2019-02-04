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
import vanillaHeaderClasses from "@library/components/headers/VanillaHeaderStyles";

interface IProps extends INotificationsProps {
    buttonClassName?: string;
    className?: string;
    contentsClassName?: string;
    countUnread: number;
    actions: NotificationsActions;
}

interface IState {
    open: boolean;
}

/**
 * Implements Notifications menu for header
 */
export class NotificationsDropDown extends React.Component<IProps, IState> {
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
        const classes = vanillaHeaderClasses();
        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={classNames(
                    "vanillaHeader-notifications",
                    classes.notifications,
                    this.props.buttonClassName,
                )}
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
                    data={this.props.data}
                    countClass={this.props.countClass}
                    userSlug={this.props.userSlug}
                    markAllRead={this.markAllNotificationsRead}
                />
            </DropDown>
        );
    }

    /**
     * A method to be invoked immediately after a component is inserted into the tree.
     */
    public componentDidMount() {
        void this.props.actions.getNotifications();
    }

    /**
     * Mark all of the current user's notifications as read, then refresh the store of notifications.
     */
    private markAllNotificationsRead = async () => {
        await this.props.actions.markAllRead();
        void this.props.actions.getNotifications();
    };

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

/**
 * Create action creators on the component, bound to a Redux dispatch function.
 *
 * @param dispatch Redux dispatch function.
 */
function mapDispatchToProps(dispatch) {
    return {
        actions: new NotificationsActions(dispatch, apiv2),
    };
}

/**
 * Update the component state, based on changes to the Redux store.
 *
 * @param state Current Redux store state.
 */
function mapStateToProps(state: INotificationsStoreState) {
    let countUnread: number = 0;
    const data: IMeBoxNotificationItem[] = [];
    const notificationsByID = get(state, "notifications.notificationsByID.data", false);

    if (notificationsByID) {
        // Tally the total unread notifications. Massage rows into something that will fit into IMeBoxNotificationItem.
        for (const notification of Object.values(notificationsByID) as INotification[]) {
            if (notification.read === false) {
                countUnread++;
            }
            data.push({
                message: notification.body,
                photo: notification.photoUrl || null,
                to: notification.url,
                recordID: notification.notificationID,
                timestamp: notification.dateUpdated,
                unread: !notification.read,
                type: MeBoxItemType.NOTIFICATION,
            });
        }

        // Notifications are indexed by ID, which means they'll be sorted by when they were inserted, ascending. Adjust for that.
        data.sort((itemA: IMeBoxNotificationItem, itemB: IMeBoxNotificationItem) => {
            const timeA = new Date(itemA.timestamp).getTime();
            const timeB = new Date(itemB.timestamp).getTime();

            if (timeA < timeB) {
                return 1;
            } else if (timeA > timeB) {
                return -1;
            } else {
                return 0;
            }
        });
    }

    return {
        countUnread,
        data,
    };
}

// Connect Redux to the React component.
export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(NotificationsDropDown);
