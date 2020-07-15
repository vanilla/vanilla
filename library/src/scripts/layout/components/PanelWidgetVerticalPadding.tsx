/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React from "react";
import { useLayout, withLayout } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";

export function PanelWidgetVerticalPadding(props: ILayoutContainer) {
    const classes = panelWidgetClasses(useLayout().mediaQueries);
    return <div className={classNames(classes.root, "hasNoHorizontalPadding", props.className)}>{props.children}</div>;
}

export default withLayout(PanelWidgetVerticalPadding);
