/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { frameClasses } from "@library/layout/frame/frameStyles";
import classNames from "classnames";
import { TouchScrollable } from "react-scrolllock";
import { inheritHeightClass } from "@library/styles/styleHelpers";

interface IProps {
    className?: string;
    header?: React.ReactNode;
    body: React.ReactNode;
    footer?: React.ReactNode;
    canGrow?: boolean; // Use this when the parent has a fixed height we want to fill.
}

/**
 * Generic "frame" component. A frame is our generic term for flyouts or modals,
 * since they often use similar components.
 */
export default function Frame(props: IProps) {
    const classes = frameClasses();

    return (
        <section className={classNames("frame", props.className, classes.root, props.canGrow && inheritHeightClass())}>
            {props.header && <div className={classes.headerWrap}> {props.header}</div>}
            <TouchScrollable>
                <div className={classes.bodyWrap}>{props.body}</div>
            </TouchScrollable>
            <div className={classes.footerWrap}>{props.footer}</div>
        </section>
    );
}
