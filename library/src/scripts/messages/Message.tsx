/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject } from "react";
import { t } from "@library/utility/appUtils";
import { LiveMessage } from "react-aria-live";
import { messagesClasses } from "@library/messages/messageStyles";
import Button from "@library/forms/Button";
import Container from "@library/layout/components/Container";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { cx } from "@emotion/css";
import { ErrorIcon } from "@library/icons/common";
import { Icon } from "@vanilla/icons";
import { IServerError } from "@library/@types/api/core";
import LinkAsButton from "@library/routing/LinkAsButton";

export type IMessageProps =
    | {
          /** Classes to be applied to the root of the component */
          className?: string;
          /** The message content as rendered by the component */
          contents?: React.ReactNode;
          /** Re-announces the message if the page gets rerendered. This is an important message, so we want this by default. */
          clearOnUnmount?: boolean;
          /** Handler for the confirm button */
          onConfirm?: () => void;
          /** Optional confirm label, defaults to "OK" */
          confirmText?: React.ReactNode;
          /** Handler for the cancel button */
          onCancel?: () => void;
          /** Optional confirm label, defaults to "Cancel" */
          cancelText?: React.ReactNode;
          /** Should the message be fixed to the top of the viewport */
          isFixed?: boolean;
          isContained?: boolean;
          /** Title displayed above the message content */
          title?: React.ReactNode;
          /** Boolean which switches confirmation and cancel text with a spinner */
          isActionLoading?: boolean;
          /** Icons that could be displayed beside the message */
          icon?: React.ReactNode | false;
          /** The kind of message */
          type?: "warning" | "error" | "neutral" | "info";
      } & (
          | {
                /** Message content for screen readers */
                stringContents: string;
            }
          | {
                error: IServerError;
            }
      );

export const Message = React.forwardRef(function Message(props: IMessageProps, ref: RefObject<HTMLDivElement>) {
    const classes = messagesClasses();

    // When fixed we need to apply an extra layer for padding.
    const InnerWrapper = props.isContained ? Container : React.Fragment;
    const OuterWrapper = props.isFixed ? Container : React.Fragment;
    const stringContents = "error" in props ? props.error.description ?? props.error.message : props.stringContents;
    let title = props.title ?? ("error" in props && props.error.description ? props.error.message : undefined);
    const contents = <div className={classes.content}>{props.contents || stringContents}</div>;

    const hasTitle = !!title;
    const isError: boolean = "error" in props || (!!props.type && props.type === "error");
    let icon = props.icon;
    if (!icon && isError) {
        icon = <ErrorIcon />;
    } else if (!icon && props.type === "warning") {
        icon = <Icon icon="notification-alert" />;
    }
    const hasIcon = !!icon;

    const content = <div className={classes.text}>{contents}</div>;
    title = title && (
        <h2
            className={cx(
                classes.title,
                // .heading prevents rich editor content from screwing up our styles.
                "heading",
            )}
        >
            {title}
        </h2>
    );

    const icon_content = !hasTitle && hasIcon; //case - if message has icon and content.
    const icon_title_content = hasTitle && hasIcon; //case - if message has icon, title and content.
    const noIcon = !hasIcon; //case - if message has title, content and no icon

    const iconMarkup = <div className={classes.iconPosition}>{icon}</div>;

    return (
        <>
            <div
                ref={ref}
                className={cx(
                    classes.root,
                    props.className,
                    {
                        [classes.fixed]: props.isFixed,
                    },
                    { [classes.error]: isError },
                    { [classes.neutral]: props.type === "neutral" },
                    { [classes.info]: props.type === "info" },
                )}
            >
                <OuterWrapper>
                    <div
                        className={cx(classes.wrap, {
                            [classes.fixed]: props.isContained,
                            [classes.wrapWithIcon]: !!icon,
                        })}
                    >
                        <InnerWrapper>
                            <div className={classes.message}>
                                {icon_content && (
                                    <div className={classes.paragraphContent}>
                                        {iconMarkup} {content}
                                    </div>
                                )}
                                {icon_title_content && (
                                    <>
                                        <div className={classes.titleContent}>
                                            {iconMarkup} {title}
                                        </div>
                                        {content}
                                    </>
                                )}
                                {noIcon && (
                                    <>
                                        {title}
                                        {content}
                                    </>
                                )}
                            </div>
                            {"error" in props && props.error && props.error.actionButton && (
                                <LinkAsButton
                                    buttonType={ButtonTypes.TEXT}
                                    to={props.error.actionButton.url}
                                    target={props.error.actionButton.target}
                                >
                                    {t(props.error.actionButton.label)}
                                </LinkAsButton>
                            )}
                            {props.onCancel && (
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={props.onCancel}
                                    className={classes.actionButton}
                                    disabled={!!props.isActionLoading}
                                >
                                    {props.isActionLoading ? <ButtonLoader /> : props.cancelText || t("Cancel")}
                                </Button>
                            )}
                            {props.onConfirm && (
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={props.onConfirm}
                                    className={cx(classes.actionButton, classes.actionButtonPrimary)}
                                    disabled={!!props.isActionLoading}
                                >
                                    {props.isActionLoading ? <ButtonLoader /> : props.confirmText || t("OK")}
                                </Button>
                            )}
                        </InnerWrapper>
                    </div>
                </OuterWrapper>
            </div>
            {/* Does not visually render, but sends message to screen reader users*/}
            {!!stringContents && (
                <LiveMessage clearOnUnmount={!!props.clearOnUnmount} message={stringContents} aria-live="assertive" />
            )}
        </>
    );
});

export default Message;
