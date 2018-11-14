/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
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
import { compose, messages } from "@library/components/icons/header";
import Count from "@library/components/mebox/pieces/Count";
import { IMeBoxMessage } from "./MeBoxMessage";
import MeBoxMessageList from "./MeBoxMessageList";

export interface IMessagesDropDownProps {
    className?: string;
    data: IMeBoxMessage[];
    count?: number;
    countClass?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default class MessagesDropDown extends React.Component<IMessagesDropDownProps, IState> {
    private id = uniqueIDFromPrefix("messagesDropDown");

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
                name={t("Messages")}
                buttonClassName={"vanillaHeader-messages meBox-button"}
                renderLeft={true}
                buttonContents={
                    <div className="meBox-buttonContent">
                        {messages(this.state.open)}
                        <Count
                            className={classNames("vanillaHeader-messagesCount", this.props.countClass)}
                            label={t("Messages: ")}
                            count={this.props.count}
                        />
                    </div>
                }
                onVisibilityChange={this.setOpen}
            >
                <Frame>
                    <FrameHeader className="isShadowed isCompact" title={t("Messages")}>
                        <LinkAsButton
                            title={t("New Message")}
                            className="headerDropDown-headerButton headerDropDown-messages button-pushRight"
                            to={"/messages/inbox"}
                            baseClass={ButtonBaseClass.TEXT}
                        >
                            {compose()}
                        </LinkAsButton>
                    </FrameHeader>
                    <FrameBody className="isSelfPadded">
                        <FramePanel>
                            <MeBoxMessageList
                                emptyMessage={t("You do not have any messages yet.")}
                                className="headerDropDown-messages"
                            >
                                {this.props.data || []}
                            </MeBoxMessageList>
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter className="isShadowed isCompact">
                        <LinkAsButton
                            className="headerDropDown-footerButton headerDropDown-allButton button-pushLeft"
                            to={"/kb/"}
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
