/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import classNames from "classnames";
import FramePanel from "@library/components/frame/FramePanel";

interface IProps {
    className?: string;
    children: JSX.Element;
}

/**
 * Generic "frame" component. A frame is our generic term for flyouts or modals,
 * since they often use similar components.
 */
export default class Frame extends React.Component<IProps> {
    public render() {
        return <section className={classNames("frame", this.props.className)}>{this.props.children}</section>;
    }
}
