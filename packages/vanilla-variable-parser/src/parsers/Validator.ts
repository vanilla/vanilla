import { parse } from "@babel/parser";

export interface IValidateSuccess<T> {
    success: true;
    value: T;
}

export interface IValidateError {
    success: false;
    error: string;
}

export type IValidateResult<T> = IValidateSuccess<T> | IValidateError;

export type ValidatorFn<T> = (val: string) => IValidateResult<T>;

export enum JsonSchemaType {
    ARRAY = "array",
    BOOLEAN = "boolean",
    INTEGER = "integer",
    STRING = "string",
    NULL = "null",
    NUMBER = "number",
    OBJECT = "object",
}

export class Validator {
    public static invalid(error: string): IValidateError {
        return {
            success: false,
            error,
        };
    }

    public static valid<T>(value: T): IValidateSuccess<T> {
        return {
            success: true,
            value,
        };
    }

    public static validateString(val: string): IValidateResult<string> {
        return Validator.valid(val);
    }

    public static validateInt(val: string): IValidateResult<number> {
        const parsed = Number.parseInt(val);
        if (Number.isNaN(parsed)) {
            return Validator.invalid("Could not parse {value} into a number");
        } else {
            return Validator.valid(parsed);
        }
    }

    public static validateJsonArray(val: string): IValidateResult<any[]> {
        try {
            const parsed = JSON.parse(val);
            if (Array.isArray(parsed)) {
                return Validator.valid(parsed);
            } else {
                return Validator.invalid("Could validate {value} into a valid array");
            }
        } catch (err) {
            return Validator.invalid("Could not parse {value} as json");
        }
    }

    public static validateJsonSchemaType(val: string): IValidateResult<JsonSchemaType[] | JsonSchemaType> {
        function extractType(type: string): JsonSchemaType | null {
            if (Object.values(JsonSchemaType).includes(type as any)) {
                return type as JsonSchemaType;
            } else {
                return null;
            }
        }

        const values = val
            .split("|")
            .map((val) => extractType(val.trim()))
            .filter((value) => value !== null) as JsonSchemaType[];
        if (values.length === 0) {
            return Validator.invalid(
                "Could not match valid JSON Schema type for {value}. Allowed values are\n" +
                    Object.values(JsonSchemaType).join(", "),
            );
        } else if (values.length === 1) {
            return Validator.valid(values[0]);
        } else {
            return Validator.valid(values);
        }
    }

    public static validateStringUnion(val: string): IValidateResult<string[]> {
        const values = val.split("|").map((val) => val.trim());

        if (values.length === 0) {
            return Validator.invalid("Unable to use an empty enum value");
        } else {
            return Validator.valid(values);
        }
    }
}
