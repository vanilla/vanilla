/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React from "react";
import { useSection, withSection } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";

export function PanelArea(props: ILayoutContainer) {
    const Tag = (props.tag as "div") || "div";
    const classes = panelAreaClasses(useSection().mediaQueries);
    return (
        <Tag ref={props.innerRef} className={classNames(classes.root, props.className)}>
            {props.children}
        </Tag>
    );
}

export default withSection(PanelArea);
