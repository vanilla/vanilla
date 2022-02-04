/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import classNames from "classnames";
import React from "react";
import { useLayout, withLayout } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";

export function PanelAreaHorizontalPadding(props: ILayoutContainer) {
    const Tag = props.tag || "div";
    const classes = panelAreaClasses(useLayout().mediaQueries);
    return <Tag className={classNames(classes.root, props.className, "hasNoVerticalPadding")}>{props.children}</Tag>;
}

export default withLayout(PanelAreaHorizontalPadding);
