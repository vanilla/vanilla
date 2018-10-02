/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

import { t } from "@library/application";
import { leftChevron } from "@library/components/Icons";
import CloseButton from "@library/components/CloseButton";
import Heading, { ICommonHeadingProps } from "@library/components/Heading";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";

interface ICommonFrameHeaderProps extends ICommonHeadingProps {
    closeFrame: () => void;
    onBackClick: () => void;
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
                    baseClass={ButtonBaseClass.CUSTOM}
                    onClick={this.props.onBackClick}
                    className="frameHeader-backButton"
                >
                    {leftChevron("frameHeader-backIcon")}
                </Button>
            );
        }

        return (
            <header className={classNames("frameHeader", this.props.className)}>
                {backLink}
                <Heading title={stringTitle!} depth={this.props.depth} className="frameHeader-heading">
                    {componentTitle}
                </Heading>
                <CloseButton className="frameHeader-close" onClick={this.props.closeFrame} />
            </header>
        );
    }
}
