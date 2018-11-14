/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import FrameHeader from "@library/components/frame/FrameHeader";
import FrameBody from "@library/components/frame/FrameBody";
import FramePanel from "@library/components/frame/FramePanel";
import FrameFooter from "@library/components/frame/FrameFooter";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import LinkAsButton from "@library/components/LinkAsButton";
import Frame from "@library/components/frame/Frame";
import { notifications, settings } from "@library/components/icons/header";
import Count from "@library/components/mebox/pieces/Count";
import classNames from "classnames";
import DropDownMessage, { IDropDownMessage } from "@library/components/mebox/pieces/DropDownMesssage";
import DropDownMessageList from "./DropDownMessageList";

export interface INotificationsDropDownProps {
    className?: string;
    data: IDropDownMessage[];
    userSlug: string;
    count?: number;
    countClass?: string;
}

interface IState {
    hasUnread: false;
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default class NotificationsDropDown extends React.Component<INotificationsDropDownProps, IState> {
    private id = uniqueIDFromPrefix("notificationsDropDown");

    public constructor(props) {
        super(props);
        this.state = {
            hasUnread: false,
            open: false,
        };
    }

    public render() {
        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={"vanillaHeader-notifications meBox-button"}
                buttonBaseClass={ButtonBaseClass.CUSTOM}
                renderLeft={true}
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
                <Frame>
                    <FrameHeader className="isShadowed isCompact" title={t("Notifications")}>
                        <LinkAsButton
                            title={t("Notification Preferences")}
                            className="headerDropDown-headerButton headerDropDown-notifications button-pushRight"
                            to={`/profile/preferences/${this.props.userSlug}`}
                            baseClass={ButtonBaseClass.TEXT}
                        >
                            {settings()}
                        </LinkAsButton>
                    </FrameHeader>
                    <FrameBody className="isSelfPadded">
                        <FramePanel>
                            <DropDownMessageList
                                emptyMessage={t("You do not have any notifications yet.")}
                                className="headerDropDown-notifications"
                            >
                                {this.props.data || []}
                            </DropDownMessageList>
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter className="isShadowed isCompact">
                        <LinkAsButton
                            className="headerDropDown-footerButton frameFooter-allButton button-pushLeft"
                            to={"/profile/notifications"}
                            baseClass={ButtonBaseClass.TEXT}
                        >
                            {t("All Notifications")}
                        </LinkAsButton>
                        {this.state.hasUnread && (
                            <Button
                                onClick={this.handleAllRead}
                                disabled={this.state.hasUnread}
                                baseClass={ButtonBaseClass.TEXT}
                                className="frameFooter-markRead"
                            >
                                {t("Mark All Read")}
                            </Button>
                        )}
                    </FrameFooter>
                </Frame>
            </DropDown>
        );
    }

    private handleAllRead = e => {
        alert("Todo!");
    };

    private setOpen = open => {
        this.setState({
            open,
        });
    };
}
