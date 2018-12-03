/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import FrameHeader from "@library/components/frame/FrameHeader";
import FrameBody from "@library/components/frame/FrameBody";
import FramePanel from "@library/components/frame/FramePanel";
import FrameFooter from "@library/components/frame/FrameFooter";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import LinkAsButton from "@library/components/LinkAsButton";
import Frame from "@library/components/frame/Frame";
import { compose } from "@library/components/icons/header";
import { IMeBoxMessageItem, MeBoxItemType } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import MeBoxDropDownItemList from "@library/components/mebox/pieces/MeBoxDropDownItemList";
import FrameHeaderWithAction from "@library/components/frame/FrameHeaderWithAction";

export interface IMessagesContentsProps {
    data: IMeBoxMessageItem[];
    count?: number;
    countClass?: string;
    panelBodyClass?: string;
    markAllRead?: () => void;
}

// For clarity, I'm adding className separately because both the container and the content have className, but it's not applied to the same element.
interface IProps extends IMessagesContentsProps {
    className?: string;
}

/**
 * Implements Messages Contents to be included in drop down or tabs
 */
export default class MessagesContents extends React.Component<IProps> {
    public render() {
        const count = this.props.count ? this.props.count : 0;
        const title = t("Messages");
        return (
            <Frame className={this.props.className}>
                <FrameHeaderWithAction title={title}>
                    <LinkAsButton
                        title={t("New Message")}
                        className="headerDropDown-headerButton headerDropDown-messages button-pushRight"
                        to={"/messages/inbox"}
                        baseClass={ButtonBaseClass.ICON}
                    >
                        {compose()}
                    </LinkAsButton>
                </FrameHeaderWithAction>
                <FrameBody className={classNames("isSelfPadded", this.props.panelBodyClass)}>
                    <FramePanel>
                        <MeBoxDropDownItemList
                            emptyMessage={t("You do not have any messages yet.")}
                            className="headerDropDown-messages"
                            type={MeBoxItemType.MESSAGE}
                            data={this.props.data || []}
                        />
                    </FramePanel>
                </FrameBody>
                <FrameFooter className="isShadowed isCompact">
                    <LinkAsButton
                        className="headerDropDown-footerButton headerDropDown-allButton button-pushLeft"
                        to={"/messages/inbox"}
                        baseClass={ButtonBaseClass.TEXT}
                    >
                        {t("All Messages")}
                    </LinkAsButton>

                    {this.props.markAllRead &&
                        count > 0 && (
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
