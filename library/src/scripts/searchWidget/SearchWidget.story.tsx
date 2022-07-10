/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { SearchWidget } from "@library/searchWidget/SearchWidget";

export default {
    title: "Widgets/SearchWidget",
    parameters: {},
};

const basicSchema: any = {
    type: "object",
    "x-form": {
        url: "/search",
        submitButtonText: "Start Here",
        searchParams: {
            domain: "discussions",
            scope: "site",
            "tagsOptions[0][value]": "{jobRole}",
            "tagsOptions[1][value]": "{industry}",
        },
    },
    properties: {
        tabID: {
            type: "string",
            const: "aboutme",
        },
        jobRole: {
            type: "number",
            "x-control": {
                inputType: "dropDown",
                label: "Job Role",
                choices: {
                    staticOptions: {
                        1: "One",
                        2: "Two",
                        3: "Three",
                    },
                },
            },
        },
        industry: {
            type: "number",
            "x-control": {
                inputType: "dropDown",
                label: "Industry",
                choices: {
                    staticOptions: {
                        1: "One",
                        2: "Two",
                        3: "Three",
                    },
                },
            },
        },
    },
    anyOf: [{ required: ["jobRole"] }, { required: ["industry"] }],
    additionalProperties: false,
};

const tabbedSchema: any = {
    type: "object",
    "x-control": {
        inputType: "tabs",
        property: "tabID",
        choices: {
            staticOptions: {
                aboutme: "About me",
                solutionarea: "Solution area",
            },
        },
    },
    properties: {
        tabID: {
            type: "string",
            default: "aboutme",
        },
    },
    required: ["tabID"],
    // This will make sure the extra properties are cleaned up when changing tabs
    discriminator: { propertyName: "tabID" },
    oneOf: [
        {
            "x-form": {
                url: "/search",
                submitButtonText: "Start Here",
                searchParams: {
                    domain: "discussions",
                    scope: "site",
                    "tagsOptions[0][value]": "{jobRole}",
                    "tagsOptions[1][value]": "{industry}",
                },
            },
            properties: {
                tabID: {
                    type: "string",
                    const: "aboutme",
                },
                jobRole: {
                    type: "number",
                    "x-control": {
                        inputType: "dropDown",
                        label: "Job Role",
                        choices: {
                            staticOptions: {
                                1: "One",
                                2: "Two",
                                3: "Three",
                            },
                        },
                    },
                },
                industry: {
                    type: "number",
                    "x-control": {
                        inputType: "dropDown",
                        label: "Industry",
                        choices: {
                            staticOptions: {
                                1: "One",
                                2: "Two",
                                3: "Three",
                            },
                        },
                    },
                },
            },
            anyOf: [{ required: ["jobRole"] }, { required: ["industry"] }],
            additionalProperties: false,
        },
        {
            "x-form": {
                url: "/search",
                submitButtonText: "Start Here",
                searchParams: {
                    domain: "discussions",
                    scope: "site",
                    "tagsOptions[0][value]": "{capability}",
                    "tagsOptions[1][value]": "{product}",
                },
            },
            properties: {
                tabID: {
                    type: "string",
                    const: "solutionarea",
                },
                capability: {
                    type: "number",
                    "x-control": {
                        inputType: "dropDown",
                        label: "Capability",
                        choices: {
                            staticOptions: {
                                1: "One",
                                2: "Two",
                                3: "Three",
                            },
                        },
                    },
                },
                product: {
                    type: "number",
                    "x-control": {
                        inputType: "dropDown",
                        label: "Product",
                        choices: {
                            staticOptions: {
                                // Test the reference functionnality for use in api objects.
                                1: "One (parent: {capability})",
                                2: "Two (parent: {capability})",
                                3: "Three (parent: {capability})",
                            },
                        },
                        conditions: [{ field: "capability", type: "number", minimum: 1, disable: true }],
                    },
                },
            },
            required: ["capability", "product"],
            additionalProperties: false,
        },
    ],
};

export const basic = storyWithConfig({}, () => (
    <>
        <StoryHeading>Basic</StoryHeading>
        <div
            style={{
                width: "100%",
                maxWidth: 480,
                margin: "auto",
                padding: 32,
            }}
        >
            <SearchWidget title="Example Title" formSchema={basicSchema} />
        </div>
    </>
));

export const tabbed = storyWithConfig({}, () => (
    <>
        <StoryHeading>Tabbed</StoryHeading>
        <div
            style={{
                width: "100%",
                maxWidth: 480,
                margin: "auto",
                padding: 32,
            }}
        >
            <SearchWidget title="Example Title" formSchema={tabbedSchema} />
        </div>
    </>
));
