/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IEditableLayout,
    ILayoutCatalog,
    LayoutEditSchema,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import omit from "lodash/omit";

/**
 * Resolve a raw layout schema with the specifications in the catalog
 */
export function hydrateLayoutFromCatalog(schema: LayoutEditSchema, catalog: ILayoutCatalog): IEditableLayout {
    const layout = schema.layout.map((node, i) => {
        const parentIndex = i;

        function hydrateNode(node: any, catalog: ILayoutCatalog, index: number = 1) {
            // Check if we need to do any resolution
            if (node !== null && typeof node === "object") {
                // If its an array, we want to resolve all the entries
                if (Array.isArray(node)) {
                    return node.map((subNode) => hydrateNode(subNode, catalog, index));
                }
                // If its an object, we need to create $reactComponent and $reactProps
                if (shouldHydrate(node)) {
                    let depth = `[${parentIndex}]`;
                    const schemaProps = Object.fromEntries(
                        Object.keys(omit(node, "$hydrate")).map((key, i) => [key, hydrateNode(node[key], catalog, i)]),
                    );
                    const { componentName, componentProps } = lookupCatalogWidget(node["$hydrate"], catalog);

                    return {
                        depth,
                        $reactComponent: componentName,
                        $reactProps: {
                            ...schemaProps,
                            ...componentProps,
                            children: schemaProps.children ?? [],
                            depth,
                        },
                    };
                }
                return node;
            }
            // By default, pass back the value unchanged
            return node;
        }

        return hydrateNode(node, catalog, i);
    });

    return {
        ...schema,
        layout,
    };
}

/**
 * Look up a widget or section by name and return it in the shape of component name and props
 */
function lookupCatalogWidget(
    widgetType: string,
    catalog: ILayoutCatalog,
): { componentName: string; componentProps: any } {
    const lookup = { ...catalog.widgets, ...catalog.sections, ...catalog.assets };

    if (lookup[widgetType]) {
        return {
            componentName: lookup[widgetType].$reactComponent,
            componentProps: {},
        };
    }
    return {
        componentName: `Widget "${widgetType}" not found`,
        componentProps: null,
    };
}

/**
 * Check for the presence of a qualifier key on the object
 */
function shouldHydrate(object: { [key: string]: any }, qualifier = "$hydrate"): boolean {
    return Object.keys(object).includes(qualifier);
}
