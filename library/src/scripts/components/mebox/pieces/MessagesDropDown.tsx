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

interface IProps extends IMessagesContentsProps {
    className?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default class MessagesDropDown extends React.Component<IProps, IState> {
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
                contentsClassName="meBox-dropDownContents"
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
                <MessagesContents data={this.props.data} count={this.props.count} countClass={this.props.countClass} />
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
