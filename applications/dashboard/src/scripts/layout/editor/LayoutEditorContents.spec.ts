/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorContents, LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorFixture } from "@dashboard/layout/editor/__fixtures__/LayoutEditor.fixtures";
import { IEditableLayoutSpec } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

describe("LayoutEditorContents", () => {
    describe("catalog hydration", () => {
        const editSpec: IEditableLayoutSpec = {
            layoutViewType: "home",
            layout: [
                {
                    $hydrate: "react.section.2-columns",
                    mainBottom: [
                        {
                            $hydrate: "react.my-widget",
                            myWidgetProp: {
                                foo: "bar",
                            },
                        },
                        {
                            $hydrate: "react.not-a-widget",
                            myProp: {
                                $hydrate: "some-hydrate",
                            },
                        },
                    ],
                },
            ],
        };
        const contents = new LayoutEditorContents(editSpec, LayoutEditorFixture.catalog());

        it("preserves the original edit spec", () => {
            expect(contents.getLayout()).toBe(editSpec.layout);
        });

        it("hydrates from the catalog", () => {
            expect(contents.hydrate()).toStrictEqual({
                layoutViewType: "home",
                layout: [
                    {
                        $reactComponent: "SectionTwoColumns",
                        $reactProps: {
                            $hydrate: "react.section.2-columns",
                            $componentName: "SectionTwoColumns",
                            $editorPath: {
                                sectionIndex: 0,
                            },
                            mainBottom: [
                                {
                                    $reactComponent: "MyWidget",
                                    $reactProps: {
                                        $hydrate: "react.my-widget",
                                        $componentName: "MyWidget",
                                        $editorPath: {
                                            sectionIndex: 0,
                                            sectionRegion: "mainBottom",
                                            sectionRegionIndex: 0,
                                        },
                                        myWidgetProp: {
                                            foo: "bar",
                                        },
                                    },
                                },
                                {
                                    $reactComponent: "react.not-a-widget",
                                    $reactProps: {
                                        $hydrate: "react.not-a-widget",
                                        $componentName: "react.not-a-widget",
                                        $editorPath: {
                                            sectionIndex: 0,
                                            sectionRegion: "mainBottom",
                                            sectionRegionIndex: 1,
                                        },
                                        myProp: {
                                            $hydrate: "some-hydrate",
                                        },
                                    },
                                },
                            ],
                        },
                    },
                ],
            });
        });
    });

    describe("modification", () => {
        it("can add widgets", () => {
            let contents = LayoutEditorFixture.contents({});
            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section1",
            });
            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section2",
            });
            contents = contents.insertWidget(
                {
                    sectionIndex: 0,
                    sectionRegion: "mainBottom",
                    sectionRegionIndex: 0,
                },
                {
                    $hydrate: "react.my-widget",
                    testID: "widget1",
                },
            );
            contents = contents.insertWidget(
                {
                    sectionIndex: 0,
                    sectionRegion: "rightTop",
                },
                {
                    $hydrate: "react.my-widget",
                    testID: "widget2",
                },
            );
            contents = contents.insertWidget(
                {
                    sectionIndex: 0,
                    sectionRegion: "rightTop",
                    sectionRegionIndex: 0,
                },
                {
                    $hydrate: "react.my-widget",
                    testID: "widget3",
                },
            );
            LayoutEditorFixture.assertContentStructure(
                {
                    section2: {
                        rightTop: ["widget3", "widget2"],
                        mainBottom: ["widget1"],
                    },
                    section1: {},
                },
                contents,
            );
        });

        it("can modify widgets", () => {
            const paramsForWidget = {
                apiParams: {
                    featured: true,
                },
                title: "My Widget",
                containerOptions: {
                    borderType: "border",
                },
            };

            const modifiedParamsForWidget = {
                apiParams: {
                    featured: false,
                },
                title: "My Widget Renamed",
                containerOptions: {
                    borderType: "shadow",
                },
            };
            let contents = LayoutEditorFixture.contents({});
            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section1",
            });

            contents = contents.insertWidget(
                {
                    sectionIndex: 0,
                    sectionRegion: "mainBottom",
                    sectionRegionIndex: 0,
                },
                {
                    $hydrate: "react.my-widget",
                    //testID is widget spec in this case
                    testID: paramsForWidget,
                },
            );

            //created widget
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {
                        mainBottom: [paramsForWidget],
                    },
                },
                contents,
            );

            contents = contents.modifyWidget(
                {
                    sectionIndex: 0,
                    sectionRegion: "mainBottom",
                    sectionRegionIndex: 0,
                },
                {
                    $hydrate: "react.my-widget",
                    //testID is widget spec in this case
                    testID: modifiedParamsForWidget,
                },
            );

            //modified widget has different params
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {
                        mainBottom: [modifiedParamsForWidget],
                    },
                },
                contents,
            );
        });

        it("can delete widgets and sections", () => {
            let contents = LayoutEditorFixture.contents({});
            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section1",
            });
            contents = contents.insertSection(1, {
                $hydrate: "react.section.2-columns",
                testID: "section2",
            });
            contents = contents.insertWidget(
                {
                    sectionIndex: 1,
                    sectionRegion: "mainBottom",
                    sectionRegionIndex: 0,
                },
                {
                    $hydrate: "react.my-widget",
                    testID: "widget1",
                },
            );
            contents = contents.insertWidget(
                {
                    sectionIndex: 0,
                    sectionRegion: "mainBottom",
                    sectionRegionIndex: 0,
                },
                {
                    $hydrate: "react.my-widget",
                    testID: "widget2",
                },
            );
            contents = contents.deleteSection(0);
            contents = contents.deleteWidget({
                sectionIndex: 0,
                sectionRegion: "mainBottom",
                sectionRegionIndex: 0,
            });
            LayoutEditorFixture.assertContentStructure(
                {
                    section2: {
                        mainBottom: [],
                    },
                },
                contents,
            );
        });

        it("can move sections", () => {
            let contents = LayoutEditorFixture.contents({});

            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section1",
            });
            contents = contents.insertSection(1, {
                $hydrate: "react.section.2-columns",
                testID: "section2",
            });
            contents = contents.insertWidget(LayoutEditorPath.widget(1, "mainBottom", 0), {
                $hydrate: "react.my-widget",
                testID: "widget1",
            });
            contents = contents.moveSection(LayoutEditorPath.section(0), LayoutEditorPath.section(1));
            LayoutEditorFixture.assertContentStructure(
                {
                    section2: {
                        mainBottom: ["widget1"],
                    },
                    section1: {},
                },
                contents,
            );
            contents = contents.moveSection(LayoutEditorPath.section(1), LayoutEditorPath.section(0));
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {},
                    section2: {
                        mainBottom: ["widget1"],
                    },
                },
                contents,
            );
        });

        it("can move widgets within a section", () => {
            let contents = LayoutEditorFixture.contents({});

            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section1",
            });
            contents = contents.insertWidget(LayoutEditorPath.destination(0, "mainBottom"), {
                $hydrate: "react.my-widget",
                testID: "widget1",
            });
            contents = contents.insertWidget(LayoutEditorPath.destination(0, "mainBottom"), {
                $hydrate: "react.my-widget",
                testID: "widget2",
            });
            contents = contents.moveWidget(
                LayoutEditorPath.widget(0, "mainBottom", 0),
                LayoutEditorPath.widget(0, "mainBottom", 1),
            );
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {
                        mainBottom: ["widget2", "widget1"],
                    },
                },
                contents,
            );
            contents = contents.moveWidget(
                LayoutEditorPath.widget(0, "mainBottom", 1),
                LayoutEditorPath.widget(0, "mainBottom", 0),
            );
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {
                        mainBottom: ["widget1", "widget2"],
                    },
                },
                contents,
            );
        });

        it("can move widgets between sections", () => {
            let contents = LayoutEditorFixture.contents({});

            contents = contents.insertSection(0, {
                $hydrate: "react.section.2-columns",
                testID: "section1",
            });
            contents = contents.insertSection(1, {
                $hydrate: "react.section.2-columns",
                testID: "section2",
            });
            contents = contents.insertWidget(LayoutEditorPath.destination(0, "mainBottom"), {
                $hydrate: "react.my-widget",
                testID: "widget1",
            });
            contents = contents.insertWidget(LayoutEditorPath.destination(1, "rightTop"), {
                $hydrate: "react.my-widget",
                testID: "widget2",
            });
            contents = contents.moveWidget(
                LayoutEditorPath.widget(0, "mainBottom", 0),
                LayoutEditorPath.widget(1, "rightTop", 1),
            );
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {
                        mainBottom: [],
                    },
                    section2: {
                        rightTop: ["widget2", "widget1"],
                    },
                },
                contents,
            );
            contents = contents.moveWidget(
                LayoutEditorPath.widget(1, "rightTop", 0),
                LayoutEditorPath.widget(0, "mainTop", 0),
            );
            LayoutEditorFixture.assertContentStructure(
                {
                    section1: {
                        mainBottom: [],
                        mainTop: ["widget2"],
                    },
                    section2: {
                        rightTop: ["widget1"],
                    },
                },
                contents,
            );
        });
    });

    describe("validation", () => {
        it("home layout view type required assets validation", () => {
            let contents = LayoutEditorFixture.contents({});
            //for now no required assets for home layout view type, but when we have one we should adjust this
            expect(contents.validate().isValid).toBe(true);
        });
        it("discussion list layout view type required assets validation", () => {
            let contents = LayoutEditorFixture.contents({}, "discussionList");

            //validation should fail unless we have required asset in spec
            expect(contents.validate().isValid).toBe(false);
            expect(contents.validate().message).toBe("Missing required widget");
            contents = contents.insertSection(0, {
                $hydrate: "react.section.1-column",
                testID: "section1",
                children: [
                    {
                        $hydrate: "react.asset.discussionList",
                        apiParams: {},
                    },
                ],
            });

            expect(contents.validate().isValid).toBe(true);
            expect(contents.validate().message).toBe(null);
        });
    });
});
