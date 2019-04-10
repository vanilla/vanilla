/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { LiveMessage } from "react-aria-live";
import { messagesClasses } from "@library/messages/messageStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";

export interface IMessageProps {
    className?: string;
    contents?: React.ReactNode;
    stringContents: string;
    clearOnUnmount?: boolean; // reannounces the message if the page gets rerendered. This is an important message, so we want this by default.
    onConfirm?: () => void;
    confirmText?: string;
    onCancel?: () => void;
    cancelText?: string;
}

export default function Message(props: IMessageProps) {
    const classes = messagesClasses();
    return (
        <>
            <div className={classNames(classes.root, props.className)}>
                <div className={classNames(classes.wrap)}>
                    <div className={classes.message}>{props.contents || props.stringContents}</div>
                    {props.onConfirm && (
                        <Button
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                            onClick={props.onConfirm}
                            className={classes.actionButton}
                        >
                            {props.confirmText || t("OK")}
                        </Button>
                    )}
                    {props.onCancel && (
                        <Button baseClass={ButtonTypes.TEXT} onClick={props.onCancel} className={classes.actionButton}>
                            {props.cancelText || t("Cancel")}
                        </Button>
                    )}
                </div>
            </div>
            {/* Does not visually render, but sends message to screen reader users*/}
            <LiveMessage clearOnUnmount={!!props.clearOnUnmount} message={props.stringContents} aria-live="assertive" />
        </>
    );
}
