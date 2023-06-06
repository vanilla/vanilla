import { Condition, IFieldError, IPtrReference } from "./types";
import get from "lodash/get";
import { logError, notEmpty } from "@vanilla/utils";
import { FormikErrors } from "formik";
import { ValidationResult, OutputUnit, Validator } from "@cfworker/json-schema";
import produce from "immer";

/**
 * Get a validation result
 */
function getValidationResult(conditions: Condition[], value: any): ValidationResult {
    /**
     * Need to use immer here because the Validator will try to augment the condition
     * and the argument is not extensible
     */
    return produce(conditions, (draft) => {
        const validator = new Validator(draft, "2020-12", false);
        return validator.validate(value);
    });
}

/**
 * Returns invalid conditions.
 * @param conditions
 * @returns
 */
export function validateConditions(conditions: Condition[], rootInstance: any) {
    const invalid = conditions.flatMap((condition) => {
        try {
            const val = get(rootInstance, condition.field ?? "", condition.default ?? null);
            // If the value is empty, bail out early
            if (!val) {
                return [condition];
            }
            const validationResult = getValidationResult(conditions, val);
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
        if (typeof value === "object" && value !== null) return findAllReferences(value, [...path, key]);
        return [];
    });
}

export function fieldErrorsToValidationErrors(fieldErrors: Record<string, IFieldError[]>): OutputUnit[] {
    const result: OutputUnit[] = [];

    for (const fieldError of Object.values(fieldErrors).flat()) {
        const instancePath = ["", fieldError.path?.replace(".", "/"), fieldError.field].filter(notEmpty).join("/");
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
