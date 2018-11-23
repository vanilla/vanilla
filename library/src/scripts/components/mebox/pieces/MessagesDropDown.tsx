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
import { messages } from "@library/components/icons/header";
import Count from "@library/components/mebox/pieces/Count";
import MessagesContents, { IMessagesContentsProps } from "@library/components/mebox/pieces/MessagesContents";
import MessagesToggle from "@library/components/mebox/pieces/MessagesToggle";

interface IProps extends IMessagesContentsProps {
    className?: string;
    buttonClassName?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default class MessagesDropDown extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("messagesDropDown");

    public state = {
        open: false,
    };

    public render() {
        const count = this.props.count ? this.props.count : 0;
        return (
            <DropDown
                id={this.id}
                name={t("Messages")}
                buttonClassName={classNames("vanillaHeader-messages", this.props.buttonClassName)}
                renderLeft={true}
                contentsClassName={"vanillaHeader-dropDownContents"}
                toggleButtonClassName="vanillaHeader-button"
                buttonContents={
                    <MessagesToggle
                        open={this.state.open}
                        count={this.props.count}
                        countClass={this.props.countClass}
                    />
                }
                onVisibilityChange={this.setOpen}
            >
                <MessagesContents data={this.props.data} count={this.props.count} countClass={this.props.countClass} />
            </DropDown>
        );
    }

    private setOpen = open => {
        this.setState({
            open,
        });
    };
}
