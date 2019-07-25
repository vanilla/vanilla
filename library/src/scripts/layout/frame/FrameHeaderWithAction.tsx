/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import FrameHeader, { IFrameHeaderProps } from "@library/layout/frame/FrameHeader";
import { ICommonHeadingProps } from "@library/layout/Heading";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import classNames from "classnames";

interface IProps extends ICommonHeadingProps {
    children: React.ReactNode;
}

/**
 * Generic header for frame with action
 */
export default class FrameHeaderWithAction extends React.PureComponent<IFrameHeaderProps> {
    public render() {
        const classes = frameHeaderClasses();
        return (
            <FrameHeader
                {...this.props}
                className={classNames(this.props.className, "frameHeaderWithAction", classes.root)}
            >
                <span className={classNames("frameHeaderWithAction-action", classes.action)}>
                    {this.props.children}
                </span>
            </FrameHeader>
        );
    }
}
