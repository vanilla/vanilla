/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { logError } from "@vanilla/utils";
import { OutputUnit, ValidationResult, Validator } from "@cfworker/json-schema";
import React, { ReactNode, useContext, useMemo } from "react";
import { IValidationResult, JSONSchemaType, JsonSchema, SchemaErrorMessage } from "./types";

type GenericFlatObject = Record<string, any>;

interface IErrorMessageTransformer {
    instancePath: string;
    keyword: string;
    newMessage: string;
}

interface IValidationContext {
    /** A function that performs validation that the JSONSchemaForm can consume */
    validate(schema: JSONSchemaType, instance: GenericFlatObject): ValidationResult;
}

/**
 * Context for form validation
 */
export const FormValidationContext = React.createContext<IValidationContext>({
    // Default to true, if the FE does not catch invalid forms, the server should
    validate: (schema: JSONSchemaType, instance: GenericFlatObject): ValidationResult => {
        logError("A ValidationContext has not been defined for this form. Any validation logic is ignored.");
        return { valid: true, errors: [] };
    },
});

/**
 * The intention of this context is to serve a generic validate method and return
 * either a validation instance, or some managed state to provide inline errors.
 *
 * Right now, this it tied to AJV and used in JSONSchemaForm
 * Future iterations should expect that the validation herein, could be used by forms managed by
 * other libraries (like Formik) or a bespoke form implementation.
 */
export function ValidationProvider(props: { children: ReactNode }) {
    const { children } = props;

    const replaceValidationMessages = (errors: OutputUnit[], schema: JSONSchemaType): OutputUnit[] => {
        // Look up error messages in the JsonSchema and replace the AJV messages
        return errors.map((error: OutputUnit) => {
            const instancePathAsKey = error.instanceLocation.replace(/\/|#/g, "");
            const hasCustomMessage = !!schema?.properties?.[instancePathAsKey]?.errorMessage;

            const getMatchingCustomMessage = (): string => {
                let messageToReturn: string = error.error ?? "";
                const errorMessage = schema?.properties?.[instancePathAsKey]?.errorMessage;

                if (errorMessage && Array.isArray(errorMessage)) {
                    const customMessage: SchemaErrorMessage | undefined = errorMessage.find(
                        (item: Record<"keyword" | "message", string>) => item?.keyword === error.keyword,
                    );
                    if (customMessage) {
                        messageToReturn = customMessage.message;
                    }
                }
                if (errorMessage && typeof errorMessage === "string") {
                    messageToReturn = errorMessage;
                }
                return messageToReturn;
            };

            return {
                ...error,
                ...(hasCustomMessage && { error: getMatchingCustomMessage() }),
            };
        });
    };

    const validate = (schema: JSONSchemaType, instance: GenericFlatObject): ValidationResult => {
        const validator = new Validator(schema, "2020-12", false);
        const validationState = validator.validate(instance);
        return {
            ...validationState,
            errors: validationState.errors.length > 0 ? replaceValidationMessages(validationState.errors, schema) : [],
        };
    };

    return <FormValidationContext.Provider value={{ validate }}>{children}</FormValidationContext.Provider>;
}

export function useFormValidation() {
    return useContext(FormValidationContext);
}
