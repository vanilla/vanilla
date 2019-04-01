/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { LiveMessage, LiveMessenger } from "react-aria-live";
import { messagesClasses } from "@library/messages/messageStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";

export interface IMessageAndRefreshProps {
    className?: string;
    message?: string;
    clearOnUnmount?: boolean; // reannounces the message if the page gets rerendered. This is an important message, so we want this by default.
}

/**
 * Message with refresh button
 */
export default class MessageAndRefresh extends React.PureComponent<IMessageAndRefreshProps> {
    public static defaultProps: Partial<IMessageAndRefreshProps> = {
        message: t("The application has been updated. Refresh to get the latest version."),
        clearOnUnmount: true,
    };

    public render() {
        const classes = messagesClasses();
        const refresh = () => {
            window.location.reload(true);
        };
        return (
            <>
                <div className={classNames(this.props.className, classes.root)}>
                    <div className={classNames(classes.wrap)}>
                        <div className={classes.message}>{this.props.message}</div>
                        <Button baseClass={ButtonTypes.TEXT} onClick={refresh} className={classes.actionButton}>
                            {t("Refresh")}
                        </Button>
                    </div>
                </div>
                {/* Does not visually render, but sends message to screen reader users*/}
                <LiveMessage
                    clearOnUnmount={this.props.clearOnUnmount}
                    message={this.props.message}
                    aria-live="polite"
                />
            </>
        );
    }
}
