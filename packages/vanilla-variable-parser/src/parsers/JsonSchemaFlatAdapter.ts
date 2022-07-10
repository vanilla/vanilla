/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { JSONSchema4, JSONSchema4Type, JSONSchema4TypeName } from "json-schema";
import { IVariable, IVariableDoc, IVariableGroup } from "./VariableParser";

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

export interface IVariableJsonSchema extends Partial<Omit<JSONSchema4, "default">>, Omit<IVariable, "key"> {
    ["x-key"]?: IVariable["key"];
    markdownDescription?: IVariable["description"];
    ["x-sinceVersion"]?: IVariable["sinceVersion"];
    ["x-deprecatedVersion"]?: IVariable["deprecatedVersion"];
    ["x-alts"]?: IVariable["alts"];

    properties?: {
        [k: string]: IVariableJsonSchema;
    };
}

/**
 * Adapter to build a flat schema.
 */
export class JsonSchemaFlatAdapter {
    public constructor(private variables: IVariable[], private varGroups: IVariableGroup[]) {}

    /**
     * Build a flat schema, with all properties being dot-notation string keys.
     */
    public asJsonSchema(): JSONSchema4 {
        const schema: JSONSchema4 = {
            type: "object",
            $schema: "http://json-schema.org/schema",
            properties: {},
        };

        const orderedVars = this.variables.sort((a, b) => {
            return a.key.localeCompare(b.key);
        });
        for (const variable of orderedVars) {
            schema.properties![variable.key] = JsonSchemaFlatAdapter.varAsSchema(variable);
        }

        return schema;
    }

    /**
     * Get a JSON schema element for a single variable or group.
     *
     * @param varDoc
     */
    private static varDocAsSchema(varDoc: IVariableDoc): Partial<JSONSchema4> {
        const { key, title, sinceVersion, deprecatedVersion, alts, description } = varDoc;
        const newValue: JSONSchema4 = {
            title,
            description,

            // Apply a "markdown" description as well for services that support it.
            // This is useful for any consumers of the schema that support rendering things like links.
            // Relevant https://github.com/microsoft/vscode-docs/pull/4180
            markdownDescription: description,
        };

        const xAttrs = {
            key,
            sinceVersion,
            deprecatedVersion,
            alts,
        };

        for (const [key, val] of Object.entries(xAttrs)) {
            if (val !== null && val !== undefined) {
                newValue[`x-${key}`] = val;
            }
        }
        return newValue;
    }

    /**
     * Convert a group into a JSON schema.
     */
    public static varGroupAsSchema(varGroup: IVariableGroup): JSONSchema4 {
        return {
            ...this.varDocAsSchema(varGroup),
            type: "object",
            properties: {},
        };
    }

    /**
     * Convert a variable into JSON schema.
     *
     * @param variable The variable.
     *
     * @return The schema or null if it was invalid.
     */
    public static varAsSchema(variable: IVariable): JSONSchema4 {
        const { type } = variable;
        if (!JsonSchemaFlatAdapter.isJSONSchema4Type(type)) {
            throw new Error(
                `Failed to adapt variable \`${variable.key}\` into JSON schema. \`${type}\` is not a valid JSON Schema type`,
            );
        }

        const newValue: JSONSchema4 = {
            ...this.varDocAsSchema(variable),
            type,
        };

        if (variable.default != null) {
            newValue.default = variable.default;
        }

        if (variable.enum != null) {
            newValue.enum = variable.enum;
        }

        return newValue;
    }

    /**
     * Check if a type or types is valid JSON schema type.
     */
    private static isJSONSchema4Type(type: string | string[]): type is JSONSchema4TypeName | JSONSchema4TypeName[] {
        if (!Array.isArray(type)) {
            type = [type];
        }

        const matchingTypes = type.filter((maybeType) => JSON_SCHEMA_TYPES.includes(maybeType as any));
        return matchingTypes.length === type.length;
    }
}
