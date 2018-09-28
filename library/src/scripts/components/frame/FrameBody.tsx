/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import FramePanel from "@library/components/frame/FramePanel";

export interface IFrameBodyProps {
    className?: string;
    children: JSX.Element[];
}

/**
 * This section goes between the header/footer. The scrolling should be done on the Panel in case they stack.
 */
export default class FrameBody extends React.PureComponent<IFrameBodyProps> {
    public render() {
        const panels = this.props.children.map(panel => {
            return <FramePanel>{panel}</FramePanel>;
        });

        return <div className={classNames("flyout-body", "inheritHeight", this.props.className)}>{panels}</div>;
    }
}
