/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import React from "react";
import Frame from "@library/layout/frame/Frame";
import classNames from "classnames";
import FrameHeaderWithAction from "@library/layout/frame/FrameHeaderWithAction";
import LinkAsButton from "@library/routing/LinkAsButton";
import { buttonUtilityClasses } from "@library/forms/Button.styles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { SettingsIcon } from "@library/icons/titleBar";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import MeBoxDropDownItemList from "@library/headers/mebox/pieces/MeBoxDropDownItemList";
import { IMeBoxNotificationItem, MeBoxItemType } from "@library/headers/mebox/pieces/MeBoxDropDownItem";

interface IState {
    open: boolean;
}

const notificationsData = [
    {
        message: "<strong>Stephane</strong> commented on <strong>Resolved</strong>",
        photo: "https://dev.vanilla.local/applications/dashboard/design/images/defaulticon.png",
        photoAlt: 'Guest: "Test"',
        to: "https://dev.vanilla.local/en-hutch/discussion/comment/1007#Comment_1007",
        recordID: 332,
        timestamp: "2018-11-27T18:32:43+00:00",
        unread: false,
        type: MeBoxItemType.NOTIFICATION,
    },
];

/**
 * Implements Notifications menu for header
 */
export default class StorybookExampleNotificationsDropDown extends React.Component<{}, IState> {
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
        const classesHeader = titleBarClasses();
        const title = t("Notifications");
        const classesFrameFooter = frameFooterClasses();
        const buttonUtils = buttonUtilityClasses();
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
            >
                <Frame
                    canGrow={true}
                    header={
                        <FrameHeaderWithAction className="hasAction" title={title}>
                            <LinkAsButton title={t("Notification Preferences")} buttonType={ButtonTypes.ICON} to={`#`}>
                                <SettingsIcon />
                            </LinkAsButton>
                        </FrameHeaderWithAction>
                    }
                    body={
                        <FrameBody className={classNames("isSelfPadded")}>
                            <MeBoxDropDownItemList
                                emptyMessage={t("You do not have any notifications yet.")}
                                className="headerDropDown-notifications"
                                type={MeBoxItemType.NOTIFICATION}
                                data={notificationsData as IMeBoxNotificationItem[]}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter>
                            <LinkAsButton
                                className={classNames(buttonUtils.pushLeft)}
                                to={"/profile/notifications"}
                                buttonType={ButtonTypes.TEXT}
                            >
                                {t("All Notifications")}
                            </LinkAsButton>
                            <Button
                                onClick={() => {
                                    return;
                                }}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={classNames("frameFooter-markRead", classesFrameFooter.markRead)}
                            >
                                {t("Mark All Read")}
                            </Button>
                        </FrameFooter>
                    }
                />
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
