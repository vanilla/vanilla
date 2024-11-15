/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Condition, EMPTY_SCHEMA, IFieldError, JsonSchema } from "./types";
import {
    fieldErrorsToValidationErrors,
    recursivelyCleanInstance,
    validateConditions,
    validationErrorsToFieldErrors,
    extractSchemaDefaults,
} from "./utils";
import { OutputUnit } from "@cfworker/json-schema";

describe("json-schema-form/utils", () => {
    describe("fieldErrorsToValidationErrors()", () => {
        it("converts the errors", () => {
            const input: Record<string, IFieldError[]> = {
                field1: [
                    {
                        message: "message1",
                        field: "field1",
                    },
                    {
                        code: "missingField",
                        message: "this will be replaced",
                        field: "field1",
                    },
                    {
                        code: "ValidateOneOrMoreArrayItemRequired",
                        message: "this will be replaced",
                        field: "field1",
                    },
                ],
                field2Nested: [
                    {
                        message: "message3",
                        field: "field2Nested",
                        path: "nest",
                    },
                ],
            };

            const expected: OutputUnit[] = [
                {
                    error: "message1",
                    keyword: "unknown",
                    instanceLocation: "#/field1",
                    keywordLocation: "fieldLevelError",
                },
                {
                    error: "Field is required.",
                    keyword: "missingField",
                    instanceLocation: "#/field1",
                    keywordLocation: "fieldLevelError",
                },
                {
                    error: "You must select at least one item.",
                    keyword: "ValidateOneOrMoreArrayItemRequired",
                    instanceLocation: "#/field1",
                    keywordLocation: "fieldLevelError",
                },
                {
                    error: "message3",
                    keyword: "unknown",
                    instanceLocation: "#/nest/field2Nested",
                    keywordLocation: "fieldLevelError",
                },
            ];
            expect(fieldErrorsToValidationErrors(input)).toStrictEqual(expected);
        });
    });

    describe("validationErrorsToFieldErrors()", () => {
        it("converts the errors", () => {
            const input: OutputUnit[] = [
                {
                    error: "message1",
                    keyword: "unknown",
                    instanceLocation: "/field1",
                    keywordLocation: "",
                },
                {
                    error: "Field is required.",
                    keyword: "missingField",
                    instanceLocation: "/field1",
                    keywordLocation: "",
                },
                {
                    error: "You must select at least one item.",
                    keyword: "ValidateOneOrMoreArrayItemRequired",
                    instanceLocation: "/field1",
                    keywordLocation: "",
                },
                {
                    error: "message3",
                    keyword: "unknown",
                    instanceLocation: "/nest/field2Nested",
                    keywordLocation: "",
                },
            ];
            const expected: IFieldError[] = [
                {
                    message: "message1",
                    code: "unknown",
                    field: "/field1",
                },
                {
                    message: "Field is required.",
                    code: "missingField",
                    field: "/field1",
                },
                {
                    message: "You must select at least one item.",
                    code: "ValidateOneOrMoreArrayItemRequired",
                    field: "/field1",
                },
                {
                    message: "message3",
                    code: "unknown",
                    field: "/nest/field2Nested",
                },
            ];

            expect(validationErrorsToFieldErrors(input)).toStrictEqual(expected);
        });
    });

    describe("recursivelyCleanInstance", () => {
        it("does not alter valid root values", () => {
            const mock = {
                stringTest: "string",
                numberTest: 100,
                booleanTest: true,
                arrayTest: [1, 2, 3],
            };
            const actual = recursivelyCleanInstance(mock);
            expect(actual).toEqual(mock);
        });
        it("does not alter valid nested values", () => {
            const mock = {
                stringTest: {
                    test: "string",
                },
                numberTest: {
                    test: 100,
                },
                booleanTest: {
                    test: true,
                },
                arrayTest: {
                    test: [1, 2, 3],
                },
            };
            const actual = recursivelyCleanInstance(mock);
            expect(actual).toEqual(mock);
        });
        it("omits invalid root values", () => {
            const mock = {
                nullTest: null,
                undefinedTest: undefined,
                booleanTest: true,
            };
            const actual = recursivelyCleanInstance(mock);
            expect(actual).toEqual({ nullTest: null, booleanTest: true });
        });
        it("omits invalid nested values", () => {
            const mock = {
                stringTest: {
                    test: "string",
                },
                nullTest: {
                    test: null,
                },
                undefinedTest: {
                    test: undefined,
                },
            };
            const actual = recursivelyCleanInstance(mock);
            expect(actual).toEqual({
                stringTest: {
                    test: "string",
                },
                nullTest: {
                    test: null,
                },
                undefinedTest: {},
            });
        });
        it("cleans deeply nested values", () => {
            const mock = {
                root: true,
                greatGrandParent: {
                    someField: "yes",
                    grandParentOne: {
                        undefinedTest: undefined,
                    },
                    grandParentTwo: {
                        someField: "yes",
                        parentOne: {
                            undefinedTest: undefined,
                        },
                        parentTwo: {
                            someField: "yes",
                            childOne: {
                                undefinedTest: undefined,
                            },
                            childTwo: {
                                someField: "yes",
                            },
                        },
                    },
                },
            };
            const expected = {
                root: true,
                greatGrandParent: {
                    someField: "yes",
                    grandParentOne: {},
                    grandParentTwo: {
                        someField: "yes",
                        parentOne: {},
                        parentTwo: {
                            someField: "yes",
                            childOne: {},
                            childTwo: {
                                someField: "yes",
                            },
                        },
                    },
                },
            };
            const actual = recursivelyCleanInstance(mock);
            expect(actual).toEqual(expected);
        });
    });

    describe("validateConditions", () => {
        const booleanConditions: Condition[] = [
            {
                field: "test",
                type: "boolean",
                const: true,
            },
        ];

        const numberConditions: Condition[] = [
            {
                field: "test",
                type: "number",
                const: 1,
            },
        ];

        const stringConditions: Condition[] = [
            {
                field: "test",
                type: "string",
                const: "testValue",
            },
        ];

        const nestedConditions: Condition[] = [
            {
                field: "test",
                type: "object",
                properties: { one: { type: "null" }, two: { const: false } },
            },
        ];

        const inverseConditions: Condition[] = [
            {
                field: "test",
                type: "string",
                not: {
                    enum: ["test"],
                },
            },
        ];

        it("inverseConditions conditions which match are invalid", () => {
            const instance = {
                test: "test",
            };
            const result = validateConditions(inverseConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("inverseConditions conditions which do not match are valid", () => {
            const instance = {
                test: "vanilla",
            };
            const result = validateConditions(inverseConditions, instance);
            expect(result.valid).toBe(true);
        });

        it("boolean conditions which match are valid", () => {
            const instance = {
                test: true,
            };
            const result = validateConditions(booleanConditions, instance);
            expect(result.valid).toBe(true);
        });
        it("boolean conditions which do not match are invalid", () => {
            const instance = {
                test: false,
            };
            const result = validateConditions(booleanConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("boolean conditions do not exists are invalid", () => {
            const conditions: Condition[] = [
                {
                    field: "test",
                    type: "boolean",
                    const: true,
                },
            ];
            const instance = {};
            const result = validateConditions(conditions, instance);
            expect(result.valid).toBe(false);
        });
        it("number conditions which match are valid", () => {
            const instance = {
                test: 1,
            };
            const result = validateConditions(numberConditions, instance);
            expect(result.valid).toBe(true);
        });
        it("number conditions which do not match are invalid", () => {
            const instance = {
                test: 0,
            };
            const result = validateConditions(numberConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("number conditions do not exists are invalid", () => {
            const instance = {};
            const result = validateConditions(numberConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("string conditions which match are valid", () => {
            const instance = {
                test: "testValue",
            };
            const result = validateConditions(stringConditions, instance);
            expect(result.valid).toBe(true);
        });
        it("string conditions which do not match are invalid", () => {
            const instance = {
                test: "notTheCorrectValue",
            };
            const result = validateConditions(stringConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("string conditions do not exists are invalid", () => {
            const instance = {};
            const result = validateConditions(stringConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("nested conditions which match are valid", () => {
            const instance = {
                test: {
                    one: null,
                    two: false,
                },
            };
            const result = validateConditions(nestedConditions, instance);
            expect(result.valid).toBe(true);
        });
        it("nested conditions which do not match are invalid", () => {
            const instance = {
                test: {
                    one: "someValue",
                    two: true,
                },
            };
            const result = validateConditions(nestedConditions, instance);
            expect(result.valid).toBe(false);
        });
        it("partial nested conditions which match are valid", () => {
            const instance = {
                test: {
                    one: null,
                },
            };
            const result = validateConditions(nestedConditions, instance);
            expect(result.valid).toBe(true);
        });
        it("nested falsy conditions do not exists are valid", () => {
            const instance = {};
            const result = validateConditions(nestedConditions, instance);
            expect(result.valid).toBe(true);
        });
        it("nested truthy conditions do not exists are invalid", () => {
            const truthyNestedConditions: Condition[] = [
                {
                    field: "test",
                    type: "object",
                    properties: { one: { type: 1 }, two: { const: true } },
                },
            ];
            const instance = {};
            const result = validateConditions(truthyNestedConditions, instance);
            expect(result.valid).toBe(false);
        });
    });

    describe("extractDataByKeyLookup", () => {
        //4 level nested complex object
        const mockWidgetSchema: JsonSchema = {
            type: "object",
            properties: {
                level1: {
                    type: "string",
                },
                level1_withDefault_value: {
                    type: "string",
                    default: "foo",
                },
                level1_withNested_value: {
                    type: "object",
                    properties: {
                        level2_withDefault_value: {
                            type: "integer",
                            default: 10,
                        },
                        level2: {
                            type: "string",
                        },
                    },
                    required: [],
                },
                level1_withDeepNested_value: {
                    type: "object",
                    properties: {
                        level2_withNested_value: {
                            type: "object",
                            properties: {
                                level3_withDefault_value: {
                                    type: "boolean",
                                    default: true,
                                },
                                level3: {
                                    type: "boolean",
                                },
                                level3_default_isUndefined: {
                                    type: "boolean",
                                    default: undefined,
                                },
                                level3_withNested_value: {
                                    type: "object",
                                    properties: {
                                        level4_deeplyNested_default_value: {
                                            type: "boolean",
                                            default: true,
                                        },
                                        level4: {
                                            type: "boolean",
                                        },
                                        level4_default_isNull: {
                                            type: "boolean",
                                            default: null,
                                        },
                                    },
                                    required: [],
                                },
                            },
                            required: [],
                        },
                        level2: {
                            type: "string",
                        },
                    },
                    required: [],
                },
            },
            required: [],
        };

        it("extract default props from widget schema", () => {
            //all values with key "default"
            expect(extractSchemaDefaults(mockWidgetSchema)).toStrictEqual({
                level1_withDefault_value: "foo",
                level1_withNested_value: {
                    level2_withDefault_value: 10,
                },
                level1_withDeepNested_value: {
                    level2_withNested_value: {
                        level3_withDefault_value: true,
                        level3_withNested_value: {
                            level4_deeplyNested_default_value: true,
                            level4_default_isNull: null,
                        },
                    },
                },
            });

            //empty object, empty object sent to lookup
            expect(extractSchemaDefaults(EMPTY_SCHEMA)).toStrictEqual({});

            expect(
                extractSchemaDefaults({
                    type: "object",
                    properties: {
                        containerOptions: {
                            type: "object",
                            default: {},
                            properties: {
                                val: {
                                    type: "string",
                                    enum: ["foo1", "foo2"],
                                    default: "foo1",
                                },
                            },
                        },
                    },
                }),
            ).toStrictEqual({
                containerOptions: {
                    val: "foo1",
                },
            });
        });
    });
});
