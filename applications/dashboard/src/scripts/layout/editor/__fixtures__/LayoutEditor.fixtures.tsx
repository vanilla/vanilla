/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorContents } from "@dashboard/layout/editor/LayoutEditorContents";
import {
    ILayoutCatalog,
    ILayoutDetails,
    INITIAL_LAYOUTS_STATE,
    LayoutViewType,
    LAYOUT_VIEW_TYPES,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { createReducer, configureStore, DeepPartial } from "@reduxjs/toolkit";
import { stableObjectHash } from "@vanilla/utils";
import React, { ReactNode } from "react";
import { Provider } from "react-redux";

type TestLayoutEditorStructure = Record<string, Record<string, string[] | string | object>>;

interface IMockLayoutState {
    config?: object;
}

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
                    allowedWidgetIDs: [],
                    schema: {},
                    name: "1 column",
                },
                "react.section.2-columns": {
                    $reactComponent: "SectionTwoColumns",
                    allowedWidgetIDs: [],
                    schema: {},
                    name: "2 columns",
                },
            },
            middlewares: {},
        };
    }

    /**
     * Create mocked section data
     */
    public static widgetData(widgetKeys: string[]) {
        return Object.fromEntries(
            widgetKeys.map((widget: string, index: number) => {
                return [
                    widget,
                    {
                        schema: {},
                        $reactComponent: "",
                        iconUrl: "test/url/path",
                        name: widget.replace("react.", "").replace(/\./gi, " "),
                    },
                ];
            }),
        );
    }

    /**
     * Create a contents using a short form.
     */
    public static contents(
        structure: TestLayoutEditorStructure,
        layoutViewType?: LayoutViewType,
    ): LayoutEditorContents {
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
                layoutViewType: layoutViewType ?? "home",
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

    public static mockLayoutDetails(overrides?: Partial<ILayoutDetails>): ILayoutDetails {
        return {
            layoutID: 1,
            name: "Test Layout",
            isDefault: false,
            insertUserID: 2,
            layoutViewType: LAYOUT_VIEW_TYPES[0],
            layoutViews: [],
            ...overrides,
        };
    }

    public static createMockLayoutsStore(state: { config?: object } = {}) {
        const testReducer = createReducer(
            {
                config: {
                    configPatchesByID: {},
                    ...state.config,
                },
                multisite: {
                    products: {
                        enabled: false,
                        enableStatus: "SUCCESS",
                        allProductLoadable: {
                            status: "SUCCESS",
                        },
                        productsById: {},
                        submittingProducts: {},
                    },
                    subcommunities: {
                        subcommunitiesByID: {
                            status: "SUCCESS",
                            data: {
                                "1": {
                                    subcommunityID: 10,
                                    name: "Test Subcommunity",
                                    folder: "en",
                                    description: "",
                                    categoryID: 16,
                                    locale: "en",
                                    url: "https://dev.vanilla.localhost/es",
                                    siteSectionGroup: "subcommunities-group-10",
                                    siteSectionID: "subcommunities-section-10",
                                    isDefault: false,
                                    sort: 1000,
                                    counts: {
                                        sourceKBs: {
                                            labelCode: "Source Knowledge Bases",
                                            count: 0,
                                        },
                                        translateKBs: {
                                            labelCode: "Translation Knowledge Bases",
                                            count: 2,
                                        },
                                        forums: {
                                            labelCode: "Forums",
                                            count: 1,
                                        },
                                    },
                                    childIDs: {
                                        categoryIDs: [16],
                                        knowledgeBaseIDs: [1, 2],
                                    },
                                },
                            },
                        },
                    },
                },
                layouts: {
                    ...INITIAL_LAYOUTS_STATE,
                },
            },
            () => {},
        );

        return configureStore({ reducer: testReducer });
    }

    public static createMockLayoutsProvider(children: ReactNode, state?: object) {
        return <Provider store={this.createMockLayoutsStore(state)}>{children}</Provider>;
    }
}
