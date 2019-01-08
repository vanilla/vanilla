/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

import { t } from "@library/application";
import CloseButton from "@library/components/CloseButton";
import Heading, { ICommonHeadingProps } from "@library/components/Heading";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { leftChevron } from "@library/components/icons/common";

export interface IFrameHeaderProps extends ICommonHeadingProps {
    closeFrame?: (e) => void; // Necessary when in modal, but not if in dropdown
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

        let backLink;
        if (this.props.onBackClick) {
            backLink = (
                <Button
                    title={backTitle}
                    aria-label={backTitle}
                    baseClass={ButtonBaseClass.ICON}
                    onClick={this.props.onBackClick}
                    className="frameHeader-backButton"
                >
                    {leftChevron("frameHeader-backIcon isSmall", true)}
                </Button>
            );
        }

        let closeButton;
        if (this.props.closeFrame) {
            closeButton = (
                <div className="frameHeader-closePosition">
                    <CloseButton className="frameHeader-close" onClick={this.props.closeFrame} />
                </div>
            );
        }

        return (
            <header className={classNames("frameHeader", this.props.className)}>
                <Heading
                    id={this.props.titleID}
                    title={this.props.title}
                    depth={this.props.depth}
                    className={classNames("frameHeader-heading", {
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
