/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import MessageAndRefresh from "@library/messages/MessageAndRefresh";
import { messagesClasses } from "@library/messages/messageStyles";
import React from "react";

export default function MessageAndRefreshFixed() {
    const classes = messagesClasses();
    return (
        <div className={classes.fixed}>
            <MessageAndRefresh className={classes.setWidth} />
        </div>
    );
}
