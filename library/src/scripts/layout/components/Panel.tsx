/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React from "react";
import { useLayout, withLayout } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";

export function Panel(props: ILayoutContainer) {
    const Tag = (props.tag as "div") || "div";
    return (
        <Tag
            className={classNames(useLayout().classes.panel, props.className)}
            aria-hidden={props.ariaHidden}
            ref={props.innerRef}
        >
            {props.children}
        </Tag>
    );
}

export default withLayout(Panel);
