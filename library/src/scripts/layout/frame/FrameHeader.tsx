/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Heading, { ICommonHeadingProps } from "@library/layout/Heading";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import CloseButton from "@library/navigation/CloseButton";
import classNames from "classnames";
import { LeftChevronIcon } from "@library/icons/common";
import { cx } from "@emotion/css";

export interface IFrameHeaderProps extends ICommonHeadingProps {
    closeFrame?: (e) => void; // Necessary when in modal, but not if in flyouts
    onBackClick?: () => void;
    titleClass?: string;
    srOnlyTitle?: boolean;
    titleID?: string;
    children?: React.ReactNode;
    borderless?: boolean;
    onClick?: () => void;
}

/**
 * Generic header for frame
 */
export default function FrameHeader(props: IFrameHeaderProps) {
    const backTitle = t("Back");
    const classes = frameHeaderClasses.useAsHook();

    let backLink;
    if (props.onBackClick) {
        backLink = (
            <Button
                title={backTitle}
                aria-label={backTitle}
                buttonType={ButtonTypes.ICON_COMPACT}
                onClick={props.onBackClick}
                className={classNames("frameHeader-backButton", classes.backButton)}
            >
                <LeftChevronIcon className={classNames(classes.backButtonIcon)} centred={true} />
            </Button>
        );
    }

    let closeButton;
    if (props.closeFrame) {
        closeButton = (
            <div className={classes.action}>
                <CloseButton className={classes.close} onClick={props.closeFrame} compact />
            </div>
        );
    }

    return (
        <header
            onClick={props.onClick}
            role={props.onClick ? "button" : undefined}
            className={cx("frameHeader", classes.root, props.borderless && classes.rootBorderLess, props.className)}
        >
            <Heading
                id={props.titleID}
                title={props.title}
                depth={props.depth ?? 2}
                className={classNames("frameHeader-heading", classes.heading, props.titleClass, {
                    "sr-only": props.srOnlyTitle,
                })}
                tabIndex={0}
            >
                {backLink}
                {props.title}
            </Heading>
            {props.children}
            {closeButton}
        </header>
    );
}
