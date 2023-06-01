import { Condition, IFieldError, IPtrReference, IValidationResult, Path } from "./types";
import get from "lodash/get";
import Ajv, { ErrorObject } from "ajv";
import { notEmpty } from "@vanilla/utils";
import { FormikErrors } from "formik";

const ajv = new Ajv({
    // Disables strict mode. Makes it possible to add unsupported properties to schemas.
    strict: false,
});

/**
 * Returns invalid conditions.
 * @param conditions
 * @returns
 */
export function validateConditions(conditions: Condition[], rootInstance: any) {
    const invalid = conditions.flatMap((condition) => {
        try {
            const val = get(rootInstance, condition.field, condition.default ?? null);
            if (!ajv.validate(condition, val)) {
                return [condition];
            }
        } catch {
            // Not able to dereference the pointer, assume it is invalid.
            return [condition];
        }
        return [];
    });
    return {
        isValid: !invalid.length,
        conditions: invalid,
    };
}

/**
 * Finds all references recursively in an object and returns their definition.
 *
 * findAllReferences({ item: { priceDisplay: "{price} $", price: 1.99 }});

 * // returns: [{ path: ["item", "priceDisplay"], ref: "price" }]
 * @param anyObj
 * @returns
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

export function fieldErrorsToValidationErrors(fieldErrors: Record<string, IFieldError[]>): ErrorObject[] {
    const result: ErrorObject[] = [];

    for (const fieldError of Object.values(fieldErrors).flat()) {
        const instancePath = ["", fieldError.path?.replace(".", "/"), fieldError.field].filter(notEmpty).join("/");
        const newError: ErrorObject = {
            keyword: fieldError.code ?? "unknown",
            instancePath,
            message: fieldError.message,
            schemaPath: "",
            params: {},
        };

        switch (newError.keyword) {
            // Kludge because these messages are all horrible (referencing API type names when we should be referencing property names).
            case "missingField":
                newError.message = "Field is required.";
                break;
            case "ValidateOneOrMoreArrayItemRequired":
                newError.message = "You must select at least one item.";
                break;
        }
        result.push(newError);
    }

    return result;
}

export function validationErrorsToFieldErrors(
    validationErrors: ErrorObject[] | null | undefined,
    pathFilter?: string,
): IFieldError[] {
    const errors =
        validationErrors
            ?.filter((error) => {
                if (!pathFilter) {
                    return true;
                } else {
                    return error.instancePath === pathFilter;
                }
            })
            .map((error) => {
                return {
                    code: error.keyword ?? "unknown",
                    message: error.message!,
                    field: error.instancePath,
                };
            }) ?? [];
    return errors;
}

export function mapAjvErrorsToFormikErrors(ajvErrors: ErrorObject[]): FormikErrors<any> {
    return Object.fromEntries(
        ajvErrors
            .filter((error) => !!error.instancePath && !!error.message)
            .map((error) => [error.instancePath, error.message])
            .map(([instancePath, message]) => {
                const key = instancePath!.slice(1, instancePath!.length).replace(/\//g, ".");
                return [key, message];
            }),
    );
}
