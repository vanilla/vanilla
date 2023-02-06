/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { logError } from "@vanilla/utils";
import Ajv, { ErrorObject } from "ajv";
import React, { ReactNode, useContext, useMemo } from "react";
import { IValidationResult, JsonSchema } from "./types";

type GenericFlatObject = Record<string, any>;

interface IErrorMessageTransformer {
    instancePath: string;
    keyword: string;
    newMessage: string;
}

interface IValidationContext {
    /** A function that performs validation that the JSONSchemaForm can consume */
    validate(schema: JsonSchema, instance: GenericFlatObject): IValidationResult;
    /** Instance of the validator, currently AJV */
    validatorInstance?: Ajv;
}

/**
 * Context for form validation
 */
export const FormValidationContext = React.createContext<IValidationContext>({
    // Default to true, if the FE does not catch invalid forms, the server should
    validate: (schema: JsonSchema, instance: GenericFlatObject) => {
        logError("A ValidationContext has not been defined for this form. Any validation logic is ignored.");
        return { isValid: true };
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

    /**
     * Copied whole sale
     * from https://github.com/vanilla/vanilla-cloud/commit/7e71d855f08142dc513945f4429d7eaefc6628db
     * Create a new AVJ instance
     */
    const ajv = useMemo(() => {
        const ajv = new Ajv({
            allErrors: true,
            // Will remove additional properties if a schema has { additionalProperties: false }.
            removeAdditional: true,
            // Will set defaults automatically.
            useDefaults: true,
            // Will make sure types match the schema.
            coerceTypes: true,
            // Lets us use discriminators to validate oneOf schemas properly (and remove additional properties)
            discriminator: true,
            // AJV is out of date and it doesn't understand the new JSON schema specs.
            strict: false,
        });
        // Add x-control as a supported keyword of the schema.
        ajv.addKeyword("x-control");
        // Add x-form as a supported keyword of the schema.
        ajv.addKeyword("x-form");
        ajv.addFormat(
            "url",
            /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_+.~#?&//=]*)/,
        );
        return ajv;
    }, []);

    const replaceValidationMessages = (errors: ErrorObject[], schema: JsonSchema): ErrorObject[] | null => {
        // Look up error messages in the JsonSchema and replace the AJV messages
        return errors.map((error: ErrorObject) => {
            const instancePathAsKey = error.instancePath.replace("/", "");
            const hasCustomMessage = !!schema?.properties[instancePathAsKey]?.errorMessage;

            const getMatchingCustomMessage = (): string => {
                let messageToReturn: string = error.message ?? "";
                if (Array.isArray(schema?.properties[instancePathAsKey]?.errorMessage)) {
                    const customMessage: Record<"keyword" | "message", string> | undefined = schema?.properties[
                        instancePathAsKey
                    ]?.errorMessage.find(
                        (item: Record<"keyword" | "message", string>) => item?.keyword === error.keyword,
                    );
                    if (customMessage) {
                        messageToReturn = customMessage.message;
                    }
                }
                if (typeof schema?.properties[instancePathAsKey]?.errorMessage === "string") {
                    messageToReturn = schema?.properties[instancePathAsKey]?.errorMessage;
                }
                return messageToReturn;
            };

            return {
                ...error,
                ...(hasCustomMessage && { message: getMatchingCustomMessage() }),
            };
        });
    };

    const validate = (schema: JsonSchema, instance: GenericFlatObject) => {
        ajv.validate(schema, instance);
        return {
            isValid: !ajv.errors || !ajv.errors.length,
            errors: ajv.errors ? replaceValidationMessages(ajv.errors, schema) : [],
        };
    };

    return (
        <FormValidationContext.Provider value={{ validate, validatorInstance: ajv }}>
            {children}
        </FormValidationContext.Provider>
    );
}

export function useFormValidation() {
    return useContext(FormValidationContext);
}
