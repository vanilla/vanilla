/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Translate from "@library/content/Translate";

//TODO this component is not done, we need to address the content

interface IWidgetSettingsPreviewProps {
    widget?: IWidgetCatalog;
}

export function WidgetSettingsPreview(props: IWidgetSettingsPreviewProps) {
    const classes = widgetSettingsClasses();
    const { widget } = props;

    return (
        <div className={classes.preview}>
            <div className={classes.previewHeader}>
                <Translate
                    source="Add or edit your widget here. You can choose your widget options by selecting a layout option, title and description if applicable. Set your widget conditions to specify where the widget will appear along with who the widget will be visible to. Find out more in the <1>documentation.</1>"
                    c1={(text) => (
                        //TODO documentation link should be here when its ready
                        <SmartLink to="">{text}</SmartLink>
                    )}
                />
            </div>
            <div className={classes.previewBody}>
                <div>Widget Preview here</div>
            </div>
        </div>
    );
}
