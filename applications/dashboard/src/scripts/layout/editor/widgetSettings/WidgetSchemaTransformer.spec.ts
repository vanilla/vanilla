/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { JsonSchema } from "@vanilla/json-schema-forms";
import { widgetsSchemaTransformer } from "@dashboard/layout/editor/widgetSettings/WidgetSchemaTransformer";

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
        mockCategoriesInitialValue["containerOptions"] = { displayType: "grid" };

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
        mockCategoriesInitialValue["containerOptions"] = { displayType: "link" };
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
});
