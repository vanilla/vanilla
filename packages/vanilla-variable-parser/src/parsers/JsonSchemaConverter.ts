import { JSONSchema4, JSONSchema4Type, JSONSchema4TypeName } from "json-schema";
import { IVariable } from "./VariableParser";

const JSON_SCHEMA_TYPES: JSONSchema4TypeName[] = [
    "string",
    "number",
    "integer",
    "boolean",
    "object",
    "array",
    "null",
    "any",
];

export class JsonSchemaConverter {
    public static convertVariables(variables: IVariable[]): JSONSchema4 {
        const schema: JSONSchema4 = {
            type: "object",
            $schema: "http://json-schema.org/schema",
            properties: {},
        };

        for (const variable of variables) {
            const { type, title, sinceVersion, deprecatedVersion, alts, description } = variable;
            if (!this.isJSONSchema4Type(type)) {
                continue;
            }

            const newValue: JSONSchema4 = {
                type,
                title,
                description,

                // Apply a "markdown" description as well for services that support it.
                // This is useful for any consumers of the schema that support rendering things like links.
                // Relevant https://github.com/microsoft/vscode-docs/pull/4180
                markdownDescription: description,
                default: variable.default,
                enum: variable.enum,
            };

            const xAttrs = {
                sinceVersion,
                deprecatedVersion,
                alts,
            };

            for (const [key, val] of Object.entries(xAttrs)) {
                if (val != null) {
                    newValue[`x-${key}`] = val;
                }
            }

            schema.properties![variable.key] = newValue;
        }

        return schema;
    }

    private static isJSONSchema4Type(type: string | string[]): type is JSONSchema4TypeName | JSONSchema4TypeName[] {
        if (!Array.isArray(type)) {
            type = [type];
        }

        const matchingTypes = type.filter((maybeType) => JSON_SCHEMA_TYPES.includes(maybeType as any));
        return matchingTypes.length === type.length;
    }
}
