/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorContents } from "@dashboard/layout/editor/LayoutEditorContents";
import { ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

type TestLayoutEditorStructure = Record<string, Record<string, string[] | string>>;

/**
 * Utilities for testing layout editor.
 */
export class LayoutEditorFixture {
    /**
     * Get a mocked catalog.
     */
    public static catalog(): ILayoutCatalog {
        return {
            layoutViewType: "home",
            layoutParams: {},
            widgets: {
                "react.my-widget": {
                    $reactComponent: "MyWidget",
                    schema: {},
                    name: "My Widget",
                },
            },
            assets: {},
            sections: {
                "react.section.1-column": {
                    $reactComponent: "SectionOneColumn",
                    recommendedWidgets: [],
                    schema: {},
                    name: "1 column",
                },
                "react.section.2-columns": {
                    $reactComponent: "SectionTwoColumns",
                    recommendedWidgets: [],
                    schema: {},
                    name: "2 columns",
                },
            },
            middleware: {},
        };
    }

    /**
     * Create a contents using a short form.
     */
    public static contents(structure: TestLayoutEditorStructure): LayoutEditorContents {
        const layoutContents: any[] = [];
        Object.entries(structure).forEach(([sectionKey, _sectionData]) => {
            const { $hydrate = "react.section.2-columns", ...sectionData } = _sectionData;
            const section = {
                $hydrate,
                testID: sectionKey,
            };
            Object.entries(sectionData).forEach(([regionName, regionDefinition]) => {
                const regionWidgetIDs = Array.isArray(regionDefinition) ? regionDefinition : [regionDefinition];
                section[regionName] = regionWidgetIDs.map((id) => {
                    return {
                        $hydrate: "react.my-widget",
                        testID: id,
                    };
                });
            });
            layoutContents.push(section);
        });

        const contents = new LayoutEditorContents(
            {
                layoutViewType: "home",
                layout: layoutContents,
            },
            this.catalog(),
        );
        return contents;
    }

    /**
     * Assert that we have some contents matching a shortform.
     */
    public static assertContentStructure(expectedStructure: TestLayoutEditorStructure, contents: LayoutEditorContents) {
        const expected = this.contents(expectedStructure);

        expect(contents.getLayout()).toStrictEqual(expected.getLayout());
    }
}
