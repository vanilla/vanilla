/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React, { useContext } from "react";
import { useSection, withSection } from "@library/layout/LayoutContext";
import { ILayoutContainer } from "@library/layout/components/interface.layoutContainer";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import PanelWidget from "@library/layout/components/PanelWidget";

export function PanelArea(props: ILayoutContainer) {
    const Tag = (props.tag as "div") || "div";
    const classes = panelAreaClasses(useSection().mediaQueries);
    const { autoWrap } = useContext(SectionBehaviourContext);
    let children = props.children;
    if (autoWrap) {
        children = (
            <>
                {React.Children.map(props.children, (child) => {
                    if (!child) {
                        return child;
                    } else if (typeof child !== "object" || !("type" in child) || child.type !== PanelWidget) {
                        return <PanelWidget>{child}</PanelWidget>;
                    } else {
                        return child;
                    }
                })}
            </>
        );
    }
    return (
        <Tag ref={props.innerRef} className={classNames(classes.root, props.className)}>
            {children}
        </Tag>
    );
}

export default withSection(PanelArea);
