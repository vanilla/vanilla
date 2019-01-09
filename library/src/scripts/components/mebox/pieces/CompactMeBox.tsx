/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { IUserFragment } from "@library/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/components/mebox/pieces/UserPhoto";
import { connect } from "react-redux";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import get from "lodash/get";
import classNames from "classnames";
import Tabs from "@library/components/tabs/Tabs";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import CloseButton from "@library/components/CloseButton";
import UserDropdownContents from "@library/components/mebox/pieces/UserDropdownContents";
import NotificationsToggle from "@library/components/mebox/pieces/NotificationsToggle";
import MessagesToggle from "@library/components/mebox/pieces/MessagesToggle";
import { IMeBoxProps } from "@library/components/mebox/MeBox";
import NotificationsContents, { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import MessagesContents, { IMessagesContentsProps } from "@library/components/mebox/pieces/MessagesContents";
import { IMeBoxItem, MeBoxItemType } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import { IConversation, INotification } from "@library/@types/api";
import NotificationsActions from "@library/notifications/NotificationsActions";
import apiv2 from "@library/apiv2";
import ConversationsActions from "@library/conversations/ConversationsActions";

export interface IUserDropDownProps extends IInjectableUserState, IMeBoxProps {
    buttonClass?: string;
    userPhotoClass?: string;
    countUnreadMessages: number;
    countUnreadNotifications: number;
}

interface IState {
    open: boolean;
}

/**
 * Implements User Drop down for header
 */
export class CompactMeBox extends React.Component<IUserDropDownProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state = {
        open: false,
    };

    public render() {
        const userInfo: IUserFragment = get(this.props, "currentUser.data", {
            name: null,
            userID: null,
            photoUrl: null,
        });

        const { counts } = this.props;
        const countClass = this.props.countsClass;
        const buttonClass = this.props.buttonClass;
        const panelContentClass = "compactMeBox-panel";
        const panelBodyClass = "compactMeBox-body";

        return (
            <div className={classNames("compactMeBox", this.props.className)}>
                <Button
                    title={t("My Account")}
                    className={classNames("compactMeBox-openButton", this.props.buttonClass)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonBaseClass.CUSTOM}
                >
                    <UserPhoto
                        userInfo={userInfo}
                        open={this.state.open}
                        className="meBox-user"
                        size={UserPhotoSize.SMALL}
                    />
                </Button>
                {this.state.open && (
                    <Modal
                        size={ModalSizes.MODAL_AS_SIDE_PANEL}
                        label={t("Article Revisions")}
                        elementToFocusOnExit={this.buttonRef.current!}
                        className="compactMeBox-modal"
                        exitHandler={this.close}
                    >
                        <div className="compactMeBox-contents">
                            <CloseButton
                                onClick={this.close}
                                className="compactMeBox-closeModal"
                                baseClass={ButtonBaseClass.CUSTOM}
                            />
                            <Tabs
                                label={t("My Account Tab")}
                                className="compactMeBox-tabs inheritHeight"
                                tabListClass="compactMeBox-tabList"
                                tabPanelsClass="compactMeBox-tabPanels inheritHeight"
                                tabPanelClass="compactMeBox-tabPanel inheritHeight"
                                buttonClass={classNames(buttonClass, "compactMeBox-tabButton")}
                                tabs={[
                                    {
                                        buttonContent: (
                                            <div className="compactMeBox-tabButtonContent">
                                                <UserPhoto
                                                    userInfo={userInfo}
                                                    open={this.state.open}
                                                    className="compactMeBox-tabButtonContent"
                                                    size={UserPhotoSize.SMALL}
                                                />
                                            </div>
                                        ),
                                        openButtonContent: (
                                            <div className="compactMeBox-tabButtonContent">
                                                <UserPhoto
                                                    userInfo={userInfo}
                                                    open={this.state.open}
                                                    className="compactMeBox-tabButtonContent"
                                                    size={UserPhotoSize.SMALL}
                                                />
                                            </div>
                                        ),
                                        panelContent: (
                                            <UserDropdownContents
                                                counts={counts}
                                                className={panelContentClass}
                                                panelBodyClass={panelBodyClass}
                                            />
                                        ),
                                    },
                                    {
                                        buttonContent: (
                                            <NotificationsToggle
                                                open={false}
                                                className="compactMeBox-tabButtonContent"
                                                count={this.props.countUnreadNotifications}
                                                countClass={this.props.notificationsProps.countClass}
                                            />
                                        ),
                                        openButtonContent: (
                                            <NotificationsToggle
                                                open={true}
                                                className="compactMeBox-tabButtonContent"
                                                count={this.props.countUnreadNotifications}
                                                countClass={this.props.notificationsProps.countClass}
                                            />
                                        ),
                                        panelContent: (
                                            <NotificationsContents
                                                {...this.props.notificationsProps}
                                                countClass={countClass}
                                                className={panelContentClass}
                                                panelBodyClass={panelBodyClass}
                                            />
                                        ),
                                    },
                                    {
                                        buttonContent: (
                                            <MessagesToggle
                                                open={false}
                                                className="compactMeBox-tabButtonContent"
                                                count={this.props.countUnreadMessages}
                                                countClass={this.props.messagesProps.countClass}
                                            />
                                        ),
                                        openButtonContent: (
                                            <MessagesToggle
                                                open={true}
                                                className="compactMeBox-tabButtonContent"
                                                count={this.props.countUnreadMessages}
                                                countClass={this.props.messagesProps.countClass}
                                            />
                                        ),
                                        panelContent: (
                                            <MessagesContents
                                                count={this.props.messagesProps.data.length}
                                                countClass={this.props.countsClass}
                                                data={this.props.messagesProps.data}
                                                className={panelContentClass}
                                                panelBodyClass={panelBodyClass}
                                            />
                                        ),
                                    },
                                ]}
                            />
                        </div>
                    </Modal>
                )}
            </div>
        );
    }

    private open = () => {
        this.setState({
            open: true,
        });
    };
    private close = () => {
        this.setState({
            open: false,
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
        notificationsActions: new NotificationsActions(dispatch, apiv2),
        conversationsActions: new ConversationsActions(dispatch, apiv2),
    };
}

/**
 * Update the component state, based on changes to the Redux store.
 *
 * @param state Current Redux store state.
 */
function mapStateToProps(state) {
    let countUnreadMessages: number = 0;
    let countUnreadNotifications: number = 0;
    const notificationsProps: INotificationsProps = {
        data: [],
        userSlug: "",
    };
    const messagesProps: IMessagesContentsProps = {
        data: [],
    };
    const conversationsByID = get(state, "conversations.conversationsByID.data", false);
    const notificationsByID = get(state, "notifications.notificationsByID.data", false);

    if (notificationsByID) {
        // Tally the total unread notifications. Massage rows into something that will fit into IMeBoxNotificationItem.
        for (const notification of Object.values(notificationsByID) as INotification[]) {
            if (notification.read === false) {
                countUnreadNotifications++;
            }
            notificationsProps.data.push({
                message: notification.body,
                photo: notification.photoUrl || null,
                to: notification.url,
                recordID: notification.notificationID,
                timestamp: notification.dateUpdated,
                unread: !notification.read,
                type: MeBoxItemType.NOTIFICATION,
            });
        }
    }

    if (conversationsByID) {
        // Tally the total unread messages. Massage rows into something that will fit into IMeBoxMessageItem.
        for (const conversation of Object.values(conversationsByID) as IConversation[]) {
            const authors: IUserFragment[] = [];
            const messageDoc = new DOMParser().parseFromString(conversation.body, "text/html");
            if (conversation.unread === true) {
                countUnreadMessages++;
            }
            conversation.participants.forEach(participant => {
                authors.push(participant.user);
            });
            messagesProps.data.push({
                authors,
                countMessages: conversation.countMessages,
                message: messageDoc.body.textContent || "",
                photo: conversation.lastMessage!.insertUser.photoUrl || null,
                to: conversation.url,
                recordID: conversation.conversationID,
                timestamp: conversation.lastMessage!.dateInserted,
                type: MeBoxItemType.MESSAGE,
                unread: conversation.unread,
            });
        }
    }

    const sortByTimestamp = (itemA: IMeBoxItem, itemB: IMeBoxItem) => {
        const timeA = new Date(itemA.timestamp).getTime();
        const timeB = new Date(itemB.timestamp).getTime();

        if (timeA < timeB) {
            return 1;
        } else if (timeA > timeB) {
            return -1;
        } else {
            return 0;
        }
    };

    notificationsProps.data.sort(sortByTimestamp);
    messagesProps.data.sort(sortByTimestamp);

    const userProps = UsersModel.mapStateToProps(state);
    const props = {
        ...userProps,
        countUnreadMessages,
        countUnreadNotifications,
        messagesProps,
        notificationsProps,
    };
    return props;
}

const withRedux = connect(mapStateToProps);
export default withRedux(CompactMeBox);
