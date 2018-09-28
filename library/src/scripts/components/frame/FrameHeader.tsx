/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import Heading from "@knowledge/components/Heading";
import BackLink from "@knowledge/components/BackLink";
import { t } from "@library/application";
import { leftChevron } from "@library/components/Icons";
import CloseButton from "@library/components/CloseButton";

interface IProps {
    className?: string;
    heading?: 2 | 3 | 4 | 5 | 6;
    parentID?: number;
    title: JSX.Element | string;
}

/**
 * Generic header for frame
 */
export default class FrameHeader extends React.PureComponent<IProps> {

    public static defaultProps = {
        heading: 2,
    };

    public render() {

        const tempClick = () => {
            alert("click");
        };

        const backTitle = t("Back");

        const heading = <Heading depth={this.props.heading}>{this.props.title}</Heading>;

        let contents;
        if (this.props.parentID) {
            contents = (
                <button className="flyoutHeader-backButton" type="button">
                    {leftChevron("flyoutHeader-backIcon")}
                    {heading}
                </button>
            );
        } else {
            contents = heading;
        }

        return (
            <header className={classNames('frameHeader', this.props.className)}>
                {contents}
                <CloseButton className="flyoutHeader-close" onClick={tempClick}/>
            </header>
        );
    }
}
