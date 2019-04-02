/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LiveMessage, LiveMessenger } from "react-aria-live";
import { messagesClasses } from "@library/messages/messageStyles";
import MessageAndRefresh, { IMessageAndRefreshProps } from "@library/messages/MessageAndRefresh";
import classNames from "classnames";

interface IProps extends IMessageAndRefreshProps {}

/**
 * Message with refresh button, fixed position
 */
export default class MessageAndRefreshFixed extends React.PureComponent<IProps> {
    public render() {
        const classes = messagesClasses();
        return (
            <div className={classes.fixed}>
                <MessageAndRefresh {...this.props} className={classNames(this.props.className, classes.setWidth)} />
            </div>
        );
    }
}
