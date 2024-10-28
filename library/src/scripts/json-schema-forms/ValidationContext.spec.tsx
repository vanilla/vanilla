/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { useFormValidation, ValidationProvider } from "./ValidationContext";
import { renderHook } from "@testing-library/react-hooks";
import { act } from "react-dom/test-utils";
import { JsonSchema } from "./types";

const MOCK_SCHEMA: JsonSchema<{ mockProperty?: string }> = {
    type: "object",
    properties: {
        mockProperty: {
            type: "string",
            nullable: true,
            minLength: 1,
            maxLength: 10,
            "x-control": {
                label: "Mock Title",
                inputType: "textBox",
            },
        },
    },
    required: [],
};

describe("ValidationProvider", () => {
    it("validate function returns no errors without wrapper", () => {
        const { result } = renderHook(() => useFormValidation());

        act(() => {
            const validation = result.current.validate(MOCK_SCHEMA, { mockProperty: "Example value" });
            expect(validation.valid).toBeTruthy();
            expect(validation.errors?.length).toBeFalsy();
        });
    });
    it("validate function returns generic error messages, when none are specified", () => {
        const wrapper = ({ children }) => <ValidationProvider>{children}</ValidationProvider>;
        const { result } = renderHook(() => useFormValidation(), { wrapper });
        act(() => {
            const validation = result.current.validate(MOCK_SCHEMA, { mockProperty: "" });
            expect(validation.valid).toBeFalsy();
            expect(validation.errors?.length).not.toBe(0);
            expect(validation?.errors?.[0]?.error).toBe("String is too short (0 < 1).");
        });
    });
    it("validate function returns custom error messages, when a generic message is specified", () => {
        const wrapper = ({ children }) => <ValidationProvider>{children}</ValidationProvider>;
        const { result } = renderHook(() => useFormValidation(), { wrapper });
        act(() => {
            const MOCK_SCHEMA_WITH_CUSTOM_MESSAGE = {
                ...MOCK_SCHEMA,
                properties: {
                    ...MOCK_SCHEMA.properties,
                    mockProperty: {
                        ...MOCK_SCHEMA.properties!.mockProperty,
                        errorMessage: "Custom error message",
                    },
                },
            };
            const validation = result.current.validate(MOCK_SCHEMA_WITH_CUSTOM_MESSAGE, {
                mockProperty: "",
            });
            expect(validation.valid).toBeFalsy();
            expect(validation.errors?.length).not.toBe(0);
            expect(validation?.errors?.[0]?.error).toBe("Custom error message");
        });
    });
    it("validate function returns custom error messages, for specific keywords", () => {
        const wrapper = ({ children }) => <ValidationProvider>{children}</ValidationProvider>;
        const { result } = renderHook(() => useFormValidation(), { wrapper });
        act(() => {
            const MOCK_SCHEMA_WITH_CUSTOM_MESSAGE: JsonSchema<{ mockProperty?: string }> = {
                ...MOCK_SCHEMA,
                properties: {
                    ...MOCK_SCHEMA.properties!,
                    mockProperty: {
                        ...MOCK_SCHEMA.properties!.mockProperty,
                        errorMessage: [
                            {
                                keyword: "minLength",
                                message: "Custom min length error message",
                            },
                            {
                                keyword: "maxLength",
                                message: "Custom max length error message",
                            },
                        ],
                    },
                },
            };
            const minLengthValidation = result.current.validate(MOCK_SCHEMA_WITH_CUSTOM_MESSAGE, {
                mockProperty: "",
            });
            expect(minLengthValidation.valid).toBeFalsy();
            expect(minLengthValidation.errors?.length).not.toBe(0);
            expect(minLengthValidation?.errors?.[0]?.error).toBe("Custom min length error message");
            const maxLengthValidation = result.current.validate(MOCK_SCHEMA_WITH_CUSTOM_MESSAGE, {
                mockProperty: "Her is a string which is longer than the maximum allowed length of this schema",
            });
            expect(maxLengthValidation.valid).toBeFalsy();
            expect(maxLengthValidation.errors?.length).not.toBe(0);
            expect(maxLengthValidation?.errors?.[0]?.error).toBe("Custom max length error message");
        });
    });
});
