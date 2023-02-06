/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ErrorObject } from "ajv";
import { IFieldError } from "./types";
import { fieldErrorsToValidationErrors, validationErrorsToFieldErrors } from "./utils";

describe("json-schema-form/utils", () => {
    describe("fieldErrorsToValidationErrors()", () => {
        const baseError = {
            params: {},
            schemaPath: "",
        };
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

            const expected: ErrorObject[] = [
                {
                    ...baseError,
                    message: "message1",
                    keyword: "unknown",
                    instancePath: "/field1",
                },
                {
                    ...baseError,
                    message: "Field is required.",
                    keyword: "missingField",
                    instancePath: "/field1",
                },
                {
                    ...baseError,
                    message: "You must select at least one item.",
                    keyword: "ValidateOneOrMoreArrayItemRequired",
                    instancePath: "/field1",
                },
                {
                    ...baseError,
                    message: "message3",
                    keyword: "unknown",
                    instancePath: "/nest/field2Nested",
                },
            ];
            expect(fieldErrorsToValidationErrors(input)).toStrictEqual(expected);
        });
    });

    describe("validationErrorsToFieldErrors()", () => {
        const baseError = {
            params: {},
            schemaPath: "",
        };
        it("converts the errors", () => {
            const input: ErrorObject[] = [
                {
                    ...baseError,
                    message: "message1",
                    keyword: "unknown",
                    instancePath: "/field1",
                },
                {
                    ...baseError,
                    message: "Field is required.",
                    keyword: "missingField",
                    instancePath: "/field1",
                },
                {
                    ...baseError,
                    message: "You must select at least one item.",
                    keyword: "ValidateOneOrMoreArrayItemRequired",
                    instancePath: "/field1",
                },
                {
                    ...baseError,
                    message: "message3",
                    keyword: "unknown",
                    instancePath: "/nest/field2Nested",
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
});
