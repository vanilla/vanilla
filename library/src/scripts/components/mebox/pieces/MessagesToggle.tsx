/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { t } from "@library/application";
import { messages } from "@library/components/icons/header";
import Count from "@library/components/mebox/pieces/Count";

interface IProps {
    count?: number;
    countClass?: string;
    open: boolean;
    className?: string;
}

/**
 * Implements Messages Drop down for header
 */
export default class MessagesToggle extends React.PureComponent<IProps> {
    private id = uniqueIDFromPrefix("messagesDropDown");

    public state = {
        open: false,
    };

    public render() {
        const count = this.props.count ? this.props.count : 0;
        return (
            <div className={classNames(this.props.className, "messagesToggle")}>
                {messages(this.props.open)}
                {count > 0 && (
                    <Count className={this.props.countClass} label={t("Messages: ")} count={this.props.count} />
                )}
            </div>
        );
    }

    private setOpen = open => {
        this.setState({
            open,
        });
    };
}
