/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import FrameHeader from "@library/components/frame/FrameHeader";
import FrameBody from "@library/components/frame/FrameBody";
import FramePanel from "@library/components/frame/FramePanel";
import FrameFooter from "@library/components/frame/FrameFooter";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import LinkAsButton from "@library/components/LinkAsButton";
import Frame from "@library/components/frame/Frame";
import { settings } from "@library/components/icons/header";
import MeBoxDropDownItemList from "@library/components/mebox/pieces/MeBoxDropDownItemList";
import { IMeBoxNotificationItem, MeBoxItemType } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import classNames from "classnames";

export interface INotificationsProps {
    data: IMeBoxNotificationItem[];
    userSlug: string;
    countClass?: string;
    panelBodyClass?: string;
    markAllRead?: () => void;
}

// For clarity, I'm adding className separately because both the container and the content have className, but it's not applied to the same element.
interface IProps extends INotificationsProps {
    className?: string;
}

/**
 * Implements Notifications Contents to be included in drop down or tabs
 */
export default class NotificationsContents extends React.Component<IProps> {
    public render() {
        return (
            <Frame className={this.props.className}>
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
                <FrameBody className={classNames("isSelfPadded", this.props.panelBodyClass)}>
                    <FramePanel>
                        <MeBoxDropDownItemList
                            emptyMessage={t("You do not have any notifications yet.")}
                            className="headerDropDown-notifications"
                            type={MeBoxItemType.NOTIFICATION}
                            data={this.props.data}
                        />
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
                    {this.props.markAllRead &&
                        this.props.data.length > 0 && (
                            <Button
                                onClick={this.props.markAllRead}
                                baseClass={ButtonBaseClass.TEXT}
                                className="frameFooter-markRead"
                            >
                                {t("Mark All Read")}
                            </Button>
                        )}
                </FrameFooter>
            </Frame>
        );
    }
}
