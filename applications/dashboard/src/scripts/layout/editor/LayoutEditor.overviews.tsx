/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    EditorSectionFullWidth,
    EditorSectionOneColumn,
    EditorSectionThreeColumns,
    EditorSectionEvenColumns,
    EditorSectionTwoColumns,
} from "@dashboard/layout/editor/LayoutEditor.sections";
import type { ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { IComponentFetcher } from "@library/features/Layout/LayoutRenderer";
import { extractSchemaDefaults } from "@library/json-schema-forms/utils";
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
    SectionThreeColumnsEven: EditorSectionEvenColumns,
    SectionTwoColumnsEven: EditorSectionEvenColumns,
});

export const fetchEditorOverviewComponent: IComponentFetcher = (componentName) => {
    return _editorOverviewComponents[componentName.toLowerCase()] ?? fetchOverviewComponent(componentName);
};

export function useEditorSchemaDefaultsEnhancer(catalog: ILayoutCatalog | null) {
    if (catalog === null) {
        return () => ({});
    }
    return (hydrateKey: string) => {
        // Ensure the rendered widgets in the layout / preview get the default values.
        const catalogItem = catalog.widgets[hydrateKey] ?? catalog.assets[hydrateKey] ?? null;
        if (catalogItem === null) {
            return {};
        }

        const defaults = extractSchemaDefaults(catalogItem.schema);
        return defaults;
    };
}
