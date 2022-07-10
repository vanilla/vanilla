/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import classNames from "classnames";

export interface IFrameBodyProps {
    className?: string;
    selfPadded?: boolean;
    hasVerticalPadding?: boolean;
    children: React.ReactNode;
    scrollable?: boolean; // DON'T use at the same time as the Modal scrollable.
}

/**
 * This section goes between the header/footer.
 * Note the each child will be split into a separate "FramePanel". This will allow animations/transitions in the future.
 */
export default class FrameBody extends React.PureComponent<IFrameBodyProps> {
    public render() {
        const classes = frameBodyClasses();
        return (
            <div
                className={classNames("frameBody", this.props.className, classes.root, {
                    isSelfPadded: this.props.selfPadded,
                    hasVerticalPadding: this.props.hasVerticalPadding,
                })}
            >
                {this.props.children}
            </div>
        );
    }
}

interface ILayoutContainer extends React.HTMLAttributes<HTMLDivElement> {
    children?: React.ReactNode;
}
/**
 * Container to apply paddings inside of a frame body.
 */
export function FrameBodyContainer(props: ILayoutContainer) {
    const classes = frameBodyClasses();
    return <div {...props} className={classNames(props.className, classes.framePaddings)} />;
}
