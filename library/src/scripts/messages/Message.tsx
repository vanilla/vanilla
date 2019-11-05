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
import Container from "@library/layout/components/Container";
import { ButtonTypes } from "@library/forms/buttonStyles";

export interface IMessageProps {
    className?: string;
    contents?: React.ReactNode;
    stringContents: string;
    clearOnUnmount?: boolean; // reannounces the message if the page gets rerendered. This is an important message, so we want this by default.
    onConfirm?: () => void;
    confirmText?: React.ReactNode;
    onCancel?: () => void;
    cancelText?: React.ReactNode;
    isFixed?: boolean;
    isFullWidth?: boolean;
}

export default function Message(props: IMessageProps) {
    const classes = messagesClasses();

    // When fixed we need to apply an extra layer for padding.
    const WrapperElement = props.isFixed ? Container : React.Fragment;
    return (
        <>
            <div className={classNames(classes.root, props.className, { [classes.fixed]: props.isFixed })}>
                <WrapperElement>
                    <div
                        className={classNames(classes.wrap, props.className, {
                            [classes.fullWidth]: props.isFullWidth,
                        })}
                    >
                        <div className={classNames(props.className, { [classes.fullWidth]: props.isFullWidth })}>
                            <div className={classes.message}>{props.contents || props.stringContents}</div>
                        </div>
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
                            <Button
                                baseClass={ButtonTypes.TEXT}
                                onClick={props.onCancel}
                                className={classes.actionButton}
                            >
                                {props.cancelText || t("Cancel")}
                            </Button>
                        )}
                    </div>
                </WrapperElement>
            </div>
            {/* Does not visually render, but sends message to screen reader users*/}
            <LiveMessage clearOnUnmount={!!props.clearOnUnmount} message={props.stringContents} aria-live="assertive" />
        </>
    );
}
