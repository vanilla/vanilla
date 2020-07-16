/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import classNames from "classnames";
import React from "react";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { useLayout, withLayout } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";

export function PanelOverflow(
    props: ILayoutContainer & { offset: number; isLeft?: boolean; renderLeftPanelBackground?: boolean },
) {
    const classes = panelAreaClasses(useLayout().mediaQueries);
    const panelVars = panelBackgroundVariables();
    const color =
        panelVars.config.render && !!props.isLeft && props.renderLeftPanelBackground
            ? panelVars.colors.backgroundColor
            : undefined;
    return (
        <div className={classes.areaOverlay}>
            <div className={classes.areaOverlayBefore(color, "left")} />
            <div ref={props.innerRef} className={classNames(props.className, classes.overflowFull(props.offset))}>
                {props.children}
            </div>
            <div className={classes.areaOverlayAfter(color, "right")} />
        </div>
    );
}

export default withLayout(PanelOverflow);
