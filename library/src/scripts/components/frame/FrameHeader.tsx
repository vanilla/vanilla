/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

import { t } from "@library/application";
import CloseButton from "@library/components/CloseButton";
import Heading, { ICommonHeadingProps } from "@library/components/Heading";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { leftChevron } from "@library/components/icons/common";
import FlexSpacer from "@library/components/FlexSpacer";

interface ICommonFrameHeaderProps extends ICommonHeadingProps {
    closeFrame?: () => void; // Necessary when in modal, but not if in dropdown
    onBackClick?: () => void;
    srOnlyTitle?: boolean;
    titleID?: string;
    children?: React.ReactNode;
    centredTitle?: boolean;
}

export interface IStringTitle extends ICommonFrameHeaderProps {
    title: string;
}

export interface IComponentTitle extends ICommonFrameHeaderProps {
    children: JSX.Element | string;
}

export type IFrameHeaderProps = IStringTitle | IComponentTitle;

/**
 * Generic header for frame
 */
export default class FrameHeader extends React.PureComponent<IFrameHeaderProps> {
    public static defaultProps = {
        heading: 2,
        srOnlyTitle: false,
        centredTitle: false,
    };

    public render() {
        const backTitle = t("Back");
        const stringTitle = "title" in this.props ? this.props.title : null;
        const componentTitle = "children" in this.props ? this.props.children : null;

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
                    {leftChevron("frameHeader-backIcon")}
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
            <header className={classNames("frameHeader", "isCompact", this.props.className)}>
                {backLink && !this.props.centredTitle ? backLink : <FlexSpacer className="frameHeader-leftSpacer" />}
                <Heading
                    id={this.props.titleID}
                    title={stringTitle!}
                    depth={this.props.depth}
                    className={classNames("frameHeader-heading", {
                        "frameHeader-left": !this.props.centredTitle,
                        "frameHeader-centred": this.props.centredTitle,
                        "sr-only": this.props.srOnlyTitle,
                    })}
                >
                    {componentTitle}
                </Heading>
                {closeButton}
                {!componentTitle ? this.props.children : null}
            </header>
        );
    }
}
