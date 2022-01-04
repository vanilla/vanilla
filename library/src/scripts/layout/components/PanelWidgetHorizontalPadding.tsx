/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React from "react";
import { useSection, withSection } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";

function PanelWidgetHorizontalPadding(props: ILayoutContainer) {
    const classes = panelWidgetClasses(useSection().mediaQueries);
    return <div className={classNames(classes.root, "hasNoVerticalPadding", props.className)}>{props.children}</div>;
}

export default withSection(PanelWidgetHorizontalPadding);
