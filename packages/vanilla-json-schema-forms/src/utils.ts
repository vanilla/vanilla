import { Condition, IFieldError, IPtrReference, JsonSchema } from "./types";
import get from "lodash/get";
import { logError, notEmpty } from "@vanilla/utils";
import { FormikErrors } from "formik";
import { ValidationResult, OutputUnit, Validator, Schema } from "@cfworker/json-schema";
import cloneDeep from "lodash/cloneDeep";

/**
 * Get a validation result
 */
export function getValidationResult(conditions: Condition, value: any): ValidationResult {
    /**
     * The Validator will try to augment the condition which is
     * not extensible so we clone it instead
     */
    const mutableConditions = cloneDeep(conditions);
    const validator = new Validator(mutableConditions as Schema, "2020-12", false);
    return validator.validate(value);
}

/**
 * Returns invalid conditions.
 * @param conditions
 * @returns
 */
export function validateConditions(conditions: Condition[], rootInstance: any) {
    const invalid = conditions.flatMap((condition) => {
        try {
            let val = get(rootInstance, condition.field ?? "", condition.default ?? null);
            if (condition.type === "object") {
                // If were checking multiple properties for conditions, value validation must occur for each field
                /**
                 * In some instances, a condition can reference an entire object and validate that each field within the
                 * object matches the instance. However, its possible for an instance not have the same shape
                 * as the schema or be entirely empty if the value is undefined.
                 *
                 * This value reassignment uses sets explicit falsy values for fields that might not exists
                 * on the instance.
                 *
                 * This is especially needed for conditionals which match falsy on initialization as the instance
                 * would be empty
                 */
                const properties = condition.properties ?? {};
                val = Object.keys(properties).reduce((acc, key) => {
                    if (!val?.[key]) {
                        if (properties[key]?.type === "null") {
                            return { ...acc, [key]: null };
                        }
                        if (properties[key]?.const === false) {
                            return { ...acc, [key]: false };
                        }
                    }
                    return val;
                }, val);
            }
            const validationResult = getValidationResult(condition, val);
            if (!validationResult.valid) {
                return [condition];
            }
        } catch (error) {
            logError(error);
            // Not able to dereference the pointer, assume it is invalid.
            return [condition];
        }
        return [];
    });
    return {
        valid: !invalid.length,
        conditions: invalid,
    };
}

/**
 * Finds all references recursively in an object and returns their definition.
 *
 * findAllReferences({ item: { priceDisplay: "{price} $", price: 1.99 }});
 */
export function findAllReferences(anyObj: any, path: Array<string | number> = []): IPtrReference[] {
    return Object.entries(anyObj).flatMap(([key, value]) => {
        if (typeof value === "string") {
            return (value.match(/{[^}]+}/g) || []).map((match) => {
                const ref = match.substr(1, match.length - 2).split(".");
                return { path: [...path, key], ref };
            });
        }
        if (typeof value === "object" && value !== null && "$$typeof" in value)
            return findAllReferences(value, [...path, key]);
        return [];
    });
}

export function fieldErrorsToValidationErrors(fieldErrors: Record<string, IFieldError[]>): OutputUnit[] {
    const result: OutputUnit[] = [];

    for (const fieldError of Object.values(fieldErrors).flat()) {
        const instancePath = ["#", fieldError.path?.replace(".", "/"), fieldError.field].filter(notEmpty).join("/");
        const newError: OutputUnit = {
            keyword: fieldError.code ?? "unknown",
            instanceLocation: instancePath,
            keywordLocation: "fieldLevelError",
            error: fieldError.message,
        };

        switch (newError.keyword) {
            // Kludge because these messages are all horrible (referencing API type names when we should be referencing property names).
            case "missingField":
                newError.error = "Field is required.";
                break;
            case "ValidateOneOrMoreArrayItemRequired":
                newError.error = "You must select at least one item.";
                break;
        }
        result.push(newError);
    }

    return result;
}

export function validationErrorsToFieldErrors(
    validationErrors: OutputUnit[] | null | undefined,
    pathFilter?: string,
): IFieldError[] {
    const errors =
        validationErrors
            ?.filter((error) => {
                if (!pathFilter) {
                    return true;
                } else {
                    return error.instanceLocation === pathFilter;
                }
            })
            .map((error) => {
                // if we're getting a root as instance location, lets go fishing for the field in the message
                if (error.instanceLocation === "#") {
                    const match = error.error.match(/(["'])(?:(?=(\\?))\2.)*?\1/);
                    if (match && match.length > 0) {
                        const field = match[0].replace(/"/g, "");
                        return {
                            code: error.keyword ?? "unknown",
                            message: error.error!,
                            field,
                        };
                    }
                }

                return {
                    code: error.keyword ?? "unknown",
                    message: error.error!,
                    field: error.instanceLocation,
                };
            }) ?? [];
    return errors;
}

export function mapValidationErrorsToFormikErrors(validationErrors: ValidationResult["errors"]): FormikErrors<any> {
    return Object.fromEntries(
        validationErrors
            .filter((error) => !!error.instanceLocation && !!error.error)
            .map((error) => [error.instanceLocation, error.error])
            .map(([instanceLocation, error]) => {
                const key = instanceLocation!.slice(1, instanceLocation!.length).replace(/\//g, ".");
                return [key, error];
            }),
    );
}

export function recursivelyCleanInstance(instance: Record<string, any>, schema?: JsonSchema) {
    return Object.keys(instance).reduce((acc, key) => {
        const value = instance[key];
        if (typeof value !== "undefined") {
            if (value !== null && typeof value === "object" && !Array.isArray(value)) {
                return { ...acc, [key]: recursivelyCleanInstance(value, schema) };
            }
            // Resolve strings used in inputs but numbers desired in schema
            if (typeof value === "string" && schema?.properties?.[key]?.type === "number") {
                // Omitting empty string values allows required number fields to be validated
                if (value?.length === 0) {
                    return { ...acc };
                }
                return { ...acc, [key]: parseInt(value) };
            }
            /**
             * Strictly speaking, required checks only assert a property on the instance, but in practice,
             * empty strings are represent omitted values in our forms.
             */
            if (
                typeof value === "string" &&
                schema?.properties?.[key]?.type === "string" &&
                schema?.required?.includes(key)
            ) {
                // Omitting empty string values allows required number fields to be validated
                if (value?.length === 0) {
                    return { ...acc };
                }
            }
            return { ...acc, [key]: value };
        }
        return acc;
    }, {});
}
