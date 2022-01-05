/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IWidgetSectionContext, WidgetSectionContext } from "@library/layout/WidgetLayout.context";
import { widgetLayoutClasses } from "@library/layout/WidgetLayout.styles";
import React from "react";

interface IProps extends Partial<IWidgetSectionContext> {
    children: React.ReactNode;
}

export function WidgetLayout(props: IProps) {
    const { children, ...contextValues } = props;
    const classes = widgetLayoutClasses();
    return (
        <WidgetSectionContext.Provider
            value={{
                widgetClass: classes.widget,
                widgetWithContainerClass: classes.widgetWithContainer,
                headingBlockClass: classes.headingBlock,
                ...contextValues,
            }}
        >
            {props.children}
        </WidgetSectionContext.Provider>
    );
}
