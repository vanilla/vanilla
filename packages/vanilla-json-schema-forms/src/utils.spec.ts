/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IFieldError } from "./types";
import { fieldErrorsToValidationErrors, validationErrorsToFieldErrors } from "./utils";
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
                    instanceLocation: "/field1",
                    keywordLocation: "fieldLevelError",
                },
                {
                    error: "Field is required.",
                    keyword: "missingField",
                    instanceLocation: "/field1",
                    keywordLocation: "fieldLevelError",
                },
                {
                    error: "You must select at least one item.",
                    keyword: "ValidateOneOrMoreArrayItemRequired",
                    instanceLocation: "/field1",
                    keywordLocation: "fieldLevelError",
                },
                {
                    error: "message3",
                    keyword: "unknown",
                    instanceLocation: "/nest/field2Nested",
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
});
