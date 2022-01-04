/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React from "react";
import { useSection, withSection } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";

export const PanelWidget = withSection(function PanelWidget(props: ILayoutContainer) {
    const classes = panelWidgetClasses(useSection().mediaQueries);
    return <div className={classNames(classes.root, props.className)}>{props.children}</div>;
});

export default PanelWidget;
