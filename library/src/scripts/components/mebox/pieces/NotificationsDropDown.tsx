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
import { IMeBoxMessage } from "@library/components/mebox/pieces/MeBoxMessage";
import MeBoxMessageList from "./MeBoxMessageList";

export interface INotificationsDropDownProps {
    className?: string;
    data: IMeBoxMessage[];
    userSlug: string;
    count?: number;
    countClass?: string;
}

interface IState {
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
                            <MeBoxMessageList
                                emptyMessage={t("You do not have any notifications yet.")}
                                className="headerDropDown-notifications"
                            >
                                {this.props.data || []}
                            </MeBoxMessageList>
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
                        {count > 0 && (
                            <Button
                                onClick={this.handleAllRead}
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
