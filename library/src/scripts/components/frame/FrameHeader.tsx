/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import Heading, { ICommonHeadingProps } from "@knowledge/components/Heading";
import { t } from "@library/application";
import { leftChevron } from "@library/components/Icons";
import CloseButton from "@library/components/CloseButton";
import Button from "@dashboard/components/forms/Button";

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

        const heading = (
            <Heading title={stringTitle!} depth={this.props.depth} className="frameHeader-heading">
                {componentTitle}
            </Heading>
        );

        let contents = heading;
        if (this.props.onBackClick) {
            contents = (
                <Button title={backTitle} onClick={this.props.onBackClick} className="frameHeader-backButton">
                    <React.Fragment>
                        {leftChevron("frameHeader-backIcon")}
                        {heading}
                    </React.Fragment>
                </Button>
            );
        }

        return (
            <header className={classNames("frameHeader", this.props.className)}>
                {contents}
                <CloseButton className="frameHeader-close" onClick={this.props.closeFrame} />
            </header>
        );
    }
}
