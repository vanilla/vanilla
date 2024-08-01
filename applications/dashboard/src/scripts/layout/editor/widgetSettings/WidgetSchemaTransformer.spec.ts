/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { JsonSchema } from "@vanilla/json-schema-forms";
import {
    setParamField,
    widgetsSchemaTransformer,
} from "@dashboard/layout/editor/widgetSettings/WidgetSchemaTransformer";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";

describe("WidgetSchemaTransformer", () => {
    const mockSchema: JsonSchema = {
        type: "object",
        description: "Random schema",
        properties: {
            apiParams: {
                type: "object",
                properties: {
                    limit: {
                        type: "number",
                        "x-control": {
                            type: "number",
                            inputType: "textBox",
                        },
                    },
                },
            },
        },
        required: [],
    };
    const mockInitialValue: any = {
        apiParams: {},
        someValue: 10,
    };
    const mockCategoriesSchema: JsonSchema = {
        type: "object",
        description: "Categories",
        properties: {
            apiParams: {
                type: "object",
                properties: {},
            },
            itemOptions: {
                properties: {
                    contentType: {
                        enum: [
                            "title-background",
                            "title-description",
                            "title-description-icon",
                            "title-description-image",
                        ],
                        "x-control": {
                            choices: {
                                staticOptions: {
                                    "title-background": "Background",
                                    "title-description": "None",
                                    "title-description-icon": "Icon",
                                    "title-description-image": "Image",
                                },
                            },
                        },
                    },
                    fallbackIcon: {},
                },
            },
            categoryOptions: {
                properties: {
                    followButton: {
                        properties: {
                            display: {
                                "x-control": "someControl",
                            },
                        },
                    },
                    metas: {
                        properties: {
                            asIcons: {
                                "x-control": "someControl",
                            },
                            display: {
                                properties: {
                                    postCount: {
                                        "x-control": "someControl",
                                    },
                                    lastPostAuthor: {
                                        "x-control": "someControl",
                                    },
                                },
                            },
                        },
                    },
                },
            },
        },
        required: [],
    };

    it("Transformer function returns the same schema/value if no tranformation made.", () => {
        const { transformedSchema, value } = widgetsSchemaTransformer(mockSchema, {}, mockInitialValue);

        // same value received
        expect(value).toEqual(mockInitialValue);
        expect(value.someValue).toBe(mockInitialValue.someValue);
        expect(value.apiParams).toBeTruthy();

        // same initial schema, plus middleware
        expect(transformedSchema.description).toBe(mockSchema.description);
        expect(transformedSchema.properties.apiParams).toMatchObject(mockSchema.properties.apiParams);
        expect(transformedSchema.properties.$middleware).toBeTruthy();
    });

    it("Categories widget transformations.", () => {
        let mockCategoriesInitialValue: any = {
            apiParams: {},
            containerOptions: {
                displayType: WidgetContainerDisplayType.LIST,
            },
        };
        const { transformedSchema } = widgetsSchemaTransformer(mockCategoriesSchema, {}, mockCategoriesInitialValue);

        const initialContentType = mockCategoriesSchema.properties.itemOptions.properties.contentType;
        let receivedContentType = transformedSchema.properties.itemOptions.properties.contentType;

        // if no containerOptions displayType as initial value, or displayType is "list", itemOptions contentType should not have "Background" option
        expect(receivedContentType.enum.find((option) => option === "title-background")).not.toBeTruthy();
        expect(receivedContentType["x-control"].choices.staticOptions["title-background"]).toBeUndefined();
        receivedContentType.enum.forEach((option) => {
            expect(initialContentType.enum.includes(option)).toBeTruthy();
            expect(receivedContentType["x-control"].choices.staticOptions[option]).toBeDefined();
        });

        // containerOptions  displayType is "grid"/"carousel", itemOptions contentType should contain all options
        mockCategoriesInitialValue["containerOptions"] = { displayType: WidgetContainerDisplayType.GRID };

        const { transformedSchema: transformedSchema1 } = widgetsSchemaTransformer(
            mockCategoriesSchema,
            {},
            mockCategoriesInitialValue,
        );
        receivedContentType = transformedSchema1.properties.itemOptions.properties.contentType;

        expect(receivedContentType.enum.length).toBe(initialContentType.enum.length);
        receivedContentType.enum.forEach((option) => {
            expect(receivedContentType["x-control"].choices.staticOptions[option]).toBeDefined();
        });

        // containerOptions  displayType is "link", no itemOptions contentType options, also, if we had initial contentType option value, we should clean that out
        mockCategoriesInitialValue["containerOptions"] = { displayType: WidgetContainerDisplayType.LINK };
        mockCategoriesInitialValue["itemOptions"] = { contentType: "title-background" };

        const { transformedSchema: transformedSchema2, value } = widgetsSchemaTransformer(
            mockCategoriesSchema,
            {},
            mockCategoriesInitialValue,
        );
        receivedContentType = transformedSchema2.properties.itemOptions.properties.contentType;

        expect(value.itemOptions.contentType).toBe("title-description");
        expect(receivedContentType["x-control"].choices).toBeUndefined();
        expect(receivedContentType["x-control"].inputType).toBe("custom");
    });

    it("Meta transformations (categories widget).", () => {
        const mockInitialValue: any = {
            apiParams: {},
            containerOptions: {
                displayType: WidgetContainerDisplayType.GRID,
            },
        };
        const { transformedSchema } = widgetsSchemaTransformer(mockCategoriesSchema, {}, mockInitialValue);

        // for grid/carousel, no follow button and limited meta
        const receivedCategoryOptions = transformedSchema.properties.categoryOptions.properties;
        expect(receivedCategoryOptions.followButton.properties.display["x-control"]).toBeUndefined();
        expect(receivedCategoryOptions.followButton.properties.display.default).toBe(false);
        expect(receivedCategoryOptions.metas.properties.display.properties.postCount["x-control"]).toBeDefined();
        expect(receivedCategoryOptions.metas.properties.display.properties.lastPostAuthor["x-control"]).toBeUndefined();

        // no meta options at all for link type
        mockInitialValue.containerOptions.displayType = WidgetContainerDisplayType.LINK;

        const { transformedSchema: transformedSchema2 } = widgetsSchemaTransformer(
            mockCategoriesSchema,
            {},
            mockInitialValue,
        );

        const receivedCategoryOptions2 = transformedSchema2.properties.categoryOptions.properties;
        expect(receivedCategoryOptions2.metas["x-control"].inputType).toBe("custom");
    });

    it("Transforms banner widget title and description schema.", () => {
        const mockBannerSchema: JsonSchema = {
            type: "object",
            description: "Banner",
            properties: {
                showTitle: {
                    type: "boolean",
                    description: "Whether or not the title should be displayed",
                    default: true,
                },
                titleType: {
                    type: "string",
                    description: "The type of title to use (contextual or static)",
                    default: "siteSection/name",
                    "x-control": {
                        description: "Select the kind of title",
                        label: "Title Type",
                        inputType: "radio",
                        choices: {
                            staticOptions: {
                                none: "None",
                                "siteSection/name": "Banner Title",
                                static: "Custom",
                            },
                        },
                    },
                },
                title: {
                    type: "string",
                    description: "Banner title.",
                    default: {
                        $hydrate: "param",
                        ref: "siteSection/name",
                    },
                    "x-control": {
                        description: "Banner title.",
                        label: "Title",
                        inputType: "textBox",
                        placeholder: "",
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
                showDescription: {
                    type: "boolean",
                    default: true,
                },
                descriptionType: {
                    type: "string",
                    description: "The type of description to use (contextual or static)",
                    default: "siteSection/description",
                    "x-control": {
                        description: "Select the kind of description",
                        label: "Description Type",
                        inputType: "radio",
                        choices: {
                            staticOptions: {
                                none: "None",
                                "siteSection/description": "Site Description",
                                static: "Custom",
                            },
                        },
                    },
                },
                description: {
                    type: "string",
                    description: "Banner description.",
                    "x-control": {
                        description: "Banner description.",
                        label: "",
                        inputType: "textBox",
                        placeholder: "Dynamic Description",
                        type: "textarea",
                        tooltip: "",
                        conditions: [
                            {
                                field: "descriptionType",
                                type: "string",
                                const: "static",
                            },
                        ],
                    },
                },
            },
        };
        const mockValueWithoutFields = {
            showTitle: true,
            showDescription: true,
            showSearch: true,
        };
        const mockValueWithoutTypes = {
            showTitle: true,
            showDescription: true,
            showSearch: true,
            title: "",
            description: "",
        };
        const mockValueWithTypes = {
            showTitle: true,
            showDescription: true,
            showSearch: true,
            titleType: "siteSection/name",
            title: {
                $hydrate: "param",
                ref: "siteSection/name",
            },
            descriptionType: "none",
        };
        const mockBannerValue = {
            background: {
                imageSource: "styleGuide",
            },
            description: "",
        };
        // The newest schema version
        const withTypes = widgetsSchemaTransformer(mockBannerSchema, {}, mockValueWithTypes);
        expect(withTypes.value).toEqual({ ...mockBannerValue, ...mockValueWithTypes });

        // Schema version where types are omitted but values are empty strings
        const withoutTypes = widgetsSchemaTransformer(mockBannerSchema, {}, mockValueWithoutTypes);
        expect(withoutTypes.value).toEqual({
            ...mockBannerValue,
            ...mockValueWithoutTypes,
            titleType: "siteSection/name",
            title: {
                $hydrate: "param",
                ref: "siteSection/name",
            },
            descriptionType: "siteSection/description",
            description: {
                $hydrate: "param",
                ref: "siteSection/description",
            },
        });

        // Schema version where entire fields are omitted
        const withoutFields = widgetsSchemaTransformer(mockBannerSchema, {}, mockValueWithoutFields);
        expect(withoutFields.value).toEqual({
            ...mockBannerValue,
            ...mockValueWithoutFields,
            titleType: "siteSection/name",
            title: {
                $hydrate: "param",
                ref: "siteSection/name",
            },
            descriptionType: "siteSection/description",
            description: {
                $hydrate: "param",
                ref: "siteSection/description",
            },
        });
    });
});

describe("setParamField", () => {
    const minimalSpec: JsonSchema = {
        type: "object",
        properties: {
            titleType: {
                type: "string",
            },
            title: {
                type: ["string", "object"],
            },
        },
    };

    it("resolves none to undefined", () => {
        const value = {
            titleType: "none",
            title: "Some old value",
        };

        const actual = setParamField("title", minimalSpec, value);
        expect(actual.resolvedValue.title).toBe("");
        expect(actual.resolvedValue.titleType).toBe("none");
    });

    it("resolves static to defined value", () => {
        const value = {
            titleType: "static",
            title: "Some defined value",
        };

        const actual = setParamField("title", minimalSpec, value);
        expect(actual.resolvedValue.title).toBe(value.title);
    });

    it("clears static value when static is selected", () => {
        const value = {
            titleType: "static",
            title: {
                $hydrate: "param",
                ref: "some.old.value",
            },
        };

        const actual = setParamField("title", minimalSpec, value);
        expect(actual.resolvedValue.title).toBe("");
    });

    it("resolves any other type value param schema", () => {
        const value = {
            titleType: "this.can.be.anything",
            title: "Some old value",
        };

        const actual = setParamField("title", minimalSpec, value);
        expect(actual.resolvedValue.title).toStrictEqual({
            $hydrate: "param",
            ref: value.titleType,
        });
    });
});
