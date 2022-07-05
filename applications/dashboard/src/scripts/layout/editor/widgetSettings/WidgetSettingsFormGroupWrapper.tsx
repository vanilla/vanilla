/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import WidgetSettingsAccordion from "@dashboard/layout/editor/widgetSettings/WidgetSettingsAccordion";
import React from "react";

interface IProps {
    header?: string;
    expandable?: boolean;
    children: React.ReactElement;
}

export default function WidgetSettingsFormGroupWrapper(props: IProps) {
    return props.header && props.expandable ? (
        <WidgetSettingsAccordion header={props.header}>{props.children}</WidgetSettingsAccordion>
    ) : (
        <>
            {props.header && <span className={widgetSettingsClasses().formGroupHeader}>{props.header}</span>}
            {props.children}
        </>
    );
}
