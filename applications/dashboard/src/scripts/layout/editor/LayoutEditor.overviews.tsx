/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    EditorSectionFullWidth,
    EditorSectionOneColumn,
    EditorSectionThreeColumns,
    EditorSectionTwoColumns,
} from "@dashboard/layout/editor/LayoutEditor.sections";
import { fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { IComponentFetcher } from "@library/features/Layout/LayoutRenderer";
import { IRegisteredComponent } from "@library/utility/componentRegistry";
import React from "react";

const _editorOverviewComponents: Record<string, IRegisteredComponent> = {};

export function registerLayoutEditorOverviews(widgets: Record<string, React.ComponentType<any>>) {
    for (const [widgetName, widget] of Object.entries(widgets)) {
        _editorOverviewComponents[widgetName.toLowerCase()] = {
            Component: widget,
        };
    }
}

registerLayoutEditorOverviews({
    SectionTwoColumns: EditorSectionTwoColumns,
    SectionThreeColumns: EditorSectionThreeColumns,
    SectionOneColumn: EditorSectionOneColumn,
    SectionFullWidth: EditorSectionFullWidth,
});

export const fetchEditorOverviewComponent: IComponentFetcher = (componentName) => {
    return _editorOverviewComponents[componentName.toLowerCase()] ?? fetchOverviewComponent(componentName);
};
