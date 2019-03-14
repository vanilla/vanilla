/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

import { t } from "../../dom/appUtils";
import CloseButton from "../../navigation/CloseButton";
import Heading, { ICommonHeadingProps } from "../Heading";
import Button from "../../forms/Button";
import { leftChevron } from "../../icons/common";
import { frameFooterClasses, frameHeaderClasses } from "library/src/scripts/layout/frame/frameStyles";
import { ButtonTypes } from "@library/styles/buttonStyles";

export interface IFrameHeaderProps extends ICommonHeadingProps {
    closeFrame?: (e) => void; // Necessary when in modal, but not if in flyouts
    onBackClick?: () => void;
    srOnlyTitle?: boolean;
    titleID?: string;
    children?: React.ReactNode;
}

/**
 * Generic header for frame
 */
export default class FrameHeader extends React.PureComponent<IFrameHeaderProps> {
    public static defaultProps = {
        heading: 2,
        srOnlyTitle: false,
    };

    public render() {
        const backTitle = t("Back");
        const classes = frameHeaderClasses();

        let backLink;
        if (this.props.onBackClick) {
            backLink = (
                <Button
                    title={backTitle}
                    aria-label={backTitle}
                    baseClass={ButtonTypes.ICON}
                    onClick={this.props.onBackClick}
                    className={classNames("frameHeader-backButton", classes.backButton)}
                >
                    {leftChevron("frameHeader-backIcon isSmall", true)}
                </Button>
            );
        }

        let closeButton;
        if (this.props.closeFrame) {
            closeButton = (
                <div className={classNames("frameHeader-closePosition", classes.closePosition, classes.action)}>
                    <CloseButton className="frameHeader-close" onClick={this.props.closeFrame} />
                </div>
            );
        }

        return (
            <header className={classNames("frameHeader", this.props.className, classes.root)}>
                <Heading
                    id={this.props.titleID}
                    title={this.props.title}
                    depth={this.props.depth}
                    className={classNames("frameHeader-heading", classes.heading, {
                        "sr-only": this.props.srOnlyTitle,
                    })}
                >
                    {backLink}
                    {this.props.title}
                </Heading>
                {this.props.children}
                {closeButton}
            </header>
        );
    }
}
