/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";
import { WidgetSettingsModal } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { StoryContent } from "@library/storybook/StoryContent";
import "../../../../../scss/admin-new.scss";
import { StoryHeading } from "@library/storybook/StoryHeading";

//FIXME FIXME this story is not generating on production build in chromatic
//https://higherlogic.atlassian.net/browse/VNLA-1459

export default {
    title: "Dashboard/LayoutEditor",
};

const widgetCatalogMock = {
    widgetID: {
        $reactComponent: "WidgetComponent",
        iconUrl: "",
        name: "WidgetName",
        schema: {
            type: "object",
            properties: {
                title: {
                    type: "string",
                    description: "Title of the widget",
                    "x-control": {
                        description: "Set a custom title.",
                        label: "Title",
                        inputType: "textBox",
                        placeholder: "",
                        type: "text",
                    },
                },
                description: {
                    type: "string",
                    description: "Description of the widget.",
                    "x-control": {
                        description: "Set a custom description.",
                        label: "Description",
                        inputType: "textBox",
                        placeholder: "",
                        type: "textarea",
                    },
                },
                subtitle: {
                    type: "string",
                    description: "Subtitle of the widget.",
                    "x-control": {
                        description: "Set a custom subtitle.",
                        label: "Subtitle",
                        inputType: "textBox",
                        placeholder: "",
                        type: "text",
                    },
                },
                apiParams: {
                    type: "object",
                    default: {},
                    description: "Api parameters for categories endpoint.",
                    properties: {
                        featured: {
                            type: "boolean",
                            description: "Followed categories filter",
                            "x-control": {
                                description: "Only featured categories.",
                                label: "Featured",
                                inputType: "toggle",
                            },
                        },
                        followed: {
                            type: "boolean",
                            description: "Followed categories filter",
                            "x-control": {
                                description: "Only followed categories.",
                                label: "Followed",
                                inputType: "toggle",
                            },
                        },
                    },
                },
                containerOptions: {
                    type: "object",
                    properties: {
                        maxColumnCounnt: {
                            description: "Set the maximum number of columns for the widget.",
                            "x-control": {
                                inputType: "dropDown",
                                label: "Max Columns",
                                description: "Set the maximum number of columns for the widget.",
                                choices: {
                                    staticOptions: {
                                        "1": "1",
                                        "2": "2",
                                        "3": "3",
                                        "4": "4",
                                        "5": "5",
                                    },
                                },
                            },
                        },
                        borderType: {
                            enum: ["border", "separator", "none", "shadow"],
                            type: "string",
                            "x-control": {
                                inputType: "dropDown",
                                description: "Choose widget border type",
                                label: "Border Type",
                                placeholder: "",
                                choices: {
                                    staticOptions: {
                                        border: "Border",
                                        none: "None",
                                        separator: "Separator",
                                        shadow: "Shadow",
                                    },
                                },
                            },
                        },
                        outerBackground: {
                            type: "object",
                            description: "Set a full width background for the container.",
                            properties: {
                                color: {
                                    type: "string",
                                    description: "Set the background color of the component.",
                                    "x-control": {
                                        description: "Pick a background color.",
                                        label: "Background color",
                                        inputType: "color",
                                    },
                                },
                            },
                        },
                    },
                },
                itemOptions: {
                    type: "object",
                    properties: {
                        imagePlacement: {
                            type: "string",
                            enum: ["left", "top"],
                            description: "Describe where image will be placed on widget item.",
                            "x-control": {
                                description: "Describe the image placement in the widget item.",
                                label: "Image Placement",
                                inputType: "dropDown",
                                placeholder: "",
                                choices: {
                                    staticOptions: {
                                        left: "Left",
                                        top: "Top",
                                    },
                                },
                            },
                        },
                    },
                },
            },
        },
    },
};

export function LayoutEditorWidgetSettingsModal() {
    const onSave = () => {
        //do something
    };
    return (
        <StoryContent>
            <StoryHeading>Widget Settings Modal with expandable form groups.</StoryHeading>
            <WidgetSettingsModal
                widgetCatalog={widgetCatalogMock}
                widgetID="widgetID"
                exitHandler={() => {}}
                onSave={onSave}
                isVisible={true}
                middlewaresCatalog={{}}
            />
        </StoryContent>
    );
}
