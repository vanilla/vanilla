/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { render, screen } from "@testing-library/react";
import { PocketContentForm } from "./PocketContentForm";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter/types";

describe("Pocket Content Form", () => {
    const mockPartialWidgetSchema = {
        widgetID: "list-discussions",
        name: "List - Discussions",
        schema: {
            type: "object",
            properties: {
                titleType: {
                    type: "string",
                    description: "The type of title to use (contextual or static)",
                    default: "none",
                    "x-control": {
                        description: "Select the kind of title",
                        label: "Title Type",
                        inputType: "radio",
                        choices: {
                            staticOptions: {
                                none: "None",
                                static: "Custom",
                            },
                        },
                        tooltipsPerOption: null,
                    },
                },
                title: {
                    type: "string",
                    description: "Title of the widget",
                    "x-control": {
                        description: "Set a custom title.",
                        label: "Title",
                        inputType: "textBox",
                        placeholder: "Type your title here",
                        type: "text",
                        tooltip: "",
                        conditions: [
                            {
                                field: "titleType",
                                type: "string",
                                const: "static",
                            },
                        ],
                    },
                },
                apiParams: {
                    type: "object",
                    default: {},
                    properties: {
                        categoryID: {
                            type: ["integer", "null"],
                            default: null,
                            "x-control": {
                                description: "Display records from this category.",
                                label: "Category",
                                inputType: "dropDown",
                                placeholder: "",
                                choices: {
                                    staticOptions: {
                                        7: "Category 7",
                                        11: "Category 11",
                                    },
                                },
                                multiple: false,
                                tooltip: "",
                                conditions: [
                                    {
                                        field: "apiParams",
                                        type: "object",
                                        properties: {
                                            siteSectionID: {
                                                type: "null",
                                            },
                                            followed: {
                                                const: false,
                                            },
                                        },
                                        required: ["siteSectionID", "followed"],
                                    },
                                ],
                            },
                        },
                        sort: {
                            type: "string",
                            default: "-dateLastComment",
                            "x-control": {
                                description: "Choose the order records are sorted.",
                                label: "Sort Order",
                                inputType: "dropDown",
                                placeholder: "",
                                choices: {
                                    staticOptions: {
                                        "-dateLastComment": "Recently Commented",
                                        "-dateInserted": "Recently Added",
                                        "-score": "Top",
                                        "-hot": "Hot (score + activity)",
                                    },
                                },
                                multiple: false,
                                tooltip: "",
                            },
                        },
                        limit: {
                            type: "integer",
                            description: "Desired number of items.",
                            minimum: 1,
                            maximum: 100,
                            step: 1,
                            default: 10,
                            "x-control": {
                                description: "Choose how many records to display.",
                                label: "Limit",
                                inputType: "textBox",
                                placeholder: "",
                                type: "number",
                                tooltip: "Up to a maximum of 100 items may be displayed.",
                            },
                        },
                    },
                    required: ["limit"],
                    description: "Configure API options",
                    "x-control": {
                        label: "Display Options",
                        description: "",
                    },
                },
            },
            required: ["apiParams"],
        },
    };
    const mockInitialWidgetParameters = {
        apiParams: {
            categoryID: 7,
            limit: 4,
            sort: "-dateInserted",
        },
        title: "my custom title",
        titleType: "static",
    };

    let mockAdapter: MockAdapter;
    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/widgets").reply(200, []);
        mockAdapter.onGet("/widgets/list-discussions").reply(200, mockPartialWidgetSchema);
    });

    it("Discussion List Widget - initial values are correctly populated in pocket content form", async () => {
        render(
            <PocketContentForm
                widgetID="list-discussions"
                format="widget"
                initialWidgetParameters={mockInitialWidgetParameters}
            />,
        );

        const titleField = await screen.findByLabelText("Title");
        const categoryField = await screen.findByLabelText("Category");
        const sortField = await screen.findByLabelText("Sort Order");
        const limitField = await screen.findByLabelText(/Limit/);

        expect(titleField.getAttribute("value")).toBe(mockInitialWidgetParameters.title);
        expect(categoryField.getAttribute("value")).toBe("Category 7");
        expect(sortField.getAttribute("value")).toBe("Recently Added");
        expect(limitField.getAttribute("value")).toBe(mockInitialWidgetParameters.apiParams.limit.toString());
    });
});
