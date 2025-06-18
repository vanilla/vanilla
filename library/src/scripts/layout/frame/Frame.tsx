/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { frameClasses } from "@library/layout/frame/frameStyles";
import classNames from "classnames";
import { TouchScrollable } from "react-scrolllock";
import { cx } from "@emotion/css";

interface IFrameProps {
    className?: string;
    header?: React.ReactNode;
    body: React.ReactNode;
    footer?: React.ReactNode;
    canGrow?: boolean; // Use this when the parent has a fixed height we want to fill.
    bodyWrapClass?: string;
    scrollable?: boolean;
}

/**
 * Generic "frame" component. A frame is our generic term for flyouts or modals,
 * since they often use similar components.
 */
export default function Frame(props: IFrameProps) {
    const { scrollable = true, canGrow = false } = props;
    const classes = frameClasses.useAsHook(canGrow);

    let content = props.body;
    if (scrollable) {
        content = (
            <TouchScrollable>
                <div className={classNames(classes.bodyWrap, props.bodyWrapClass)}>{content}</div>
            </TouchScrollable>
        );
    }

    return (
        <section className={cx("frame", classes.root, props.className)}>
            {props.header && <div className={classes.headerWrap}> {props.header}</div>}
            {content}
            <div className={classes.footerWrap}>{props.footer}</div>
        </section>
    );
}
