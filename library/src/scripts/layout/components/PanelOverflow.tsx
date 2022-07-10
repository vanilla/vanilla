/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import classNames from "classnames";
import React, { useContext } from "react";
import { panelBackgroundVariables } from "@library/layout/PanelBackground.variables";
import { useSection, withSection } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";

export function PanelOverflow(
    props: ILayoutContainer & { offset: number; isLeft?: boolean; renderLeftPanelBackground?: boolean },
) {
    const { useMinHeight } = useContext(SectionBehaviourContext);
    const classes = panelAreaClasses(useSection().mediaQueries);
    const panelVars = panelBackgroundVariables();
    const color =
        panelVars.config.render && !!props.isLeft && props.renderLeftPanelBackground
            ? panelVars.colors.backgroundColor
            : undefined;
    return (
        <div className={classes.areaOverlay}>
            {useMinHeight && <div className={classes.areaOverlayBefore(color, "left")} />}
            <div
                ref={props.innerRef}
                className={classNames(props.className, classes.overflowFull(props.offset, useMinHeight))}
            >
                {props.children}
            </div>
            {useMinHeight && <div className={classes.areaOverlayAfter(color, "right")} />}
        </div>
    );
}

export default withSection(PanelOverflow);
