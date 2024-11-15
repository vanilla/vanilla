/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { JsonSchema, PartialSchemaDefinition } from "@library/json-schema-forms";
import { useOpenApiContext } from "@library/openapi/OpenApiContext";
import type { IOpenApiSpec, OpenApiRef } from "@library/openapi/OpenApiTypes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import get from "lodash-es/get";

export function resolveOpenApiRef<T extends object | undefined>(spec: IOpenApiSpec, maybeRef: T | OpenApiRef): T {
    if (!maybeRef) {
        return maybeRef;
    }
    if (maybeRef != null && typeof maybeRef === "object" && "$ref" in maybeRef) {
        const ref = maybeRef.$ref;
        const refPath = ref.split("/").slice(1);
        const refContents = get(spec, refPath, {});
        const last = refPath[refPath.length - 1];
        refContents["x-label"] = last;

        return refContents as T;
    } else {
        return maybeRef as T;
    }
}

export function resolveOpenApiRefRecursively<T extends object | undefined>(
    spec: IOpenApiSpec,
    maybeRef: T | OpenApiRef,
): T {
    let result = resolveOpenApiRef(spec, maybeRef);
    if (Array.isArray(result)) {
        return result.map((item) => resolveOpenApiRefRecursively(spec, item)) as T;
    } else if (result != null && typeof result === "object") {
        return Object.fromEntries(
            Object.entries(result).map(([key, value]) => {
                return [key, resolveOpenApiRefRecursively(spec, value)];
            }),
        ) as T;
    }
    return result;
}

export function useResolvedOpenApiSchema<T extends object | undefined>(schema: T | OpenApiRef): T {
    const { spec } = useOpenApiContext();

    const resolvedSchema = resolveOpenApiRef(spec, schema);
    if (resolvedSchema && "items" in resolvedSchema) {
        resolvedSchema.items = resolveOpenApiRef(spec, resolvedSchema.items as any);
    }

    return resolvedSchema;
}

export function openApiMethodColor(method: string): string {
    switch (method) {
        case "get":
            return "#0082d0";
        case "post":
            return "#069061";
        case "put":
            return "#5203d1";
        case "delete":
            return "#dc1b19";
        case "options":
            return "#3e3d3d";
        case "head":
            return "#ff8d4d";
        case "patch":
            return "#ff8d4d";
        default:
            return ColorsUtils.colorOut(globalVariables().mainColors.fg)!;
    }
}

export function sortRequiredSchemaPropertiesFirst(schema: JsonSchema): JsonSchema {
    if (!schema) {
        return schema;
    }
    if (!schema.properties) {
        return schema;
    }

    if (!schema.required || schema.required.length === 0) {
        return schema;
    }

    const newPropertiesPieces = Object.entries(schema.properties).sort(([a, aSchema], [b, bSchema]) => {
        const aRequired = schema.required.includes(a) && aSchema.type !== "boolean";
        const bRequired = schema.required.includes(b) && bSchema.type !== "boolean";

        if (aRequired && !bRequired) {
            return -1;
        }

        if (bRequired && !aRequired) {
            return 1;
        }

        return 0;
    });

    const result = {
        ...schema,
        properties: Object.fromEntries(newPropertiesPieces),
    };

    return result;
}

export function jsonSchemaType(schema: PartialSchemaDefinition): React.ReactNode {
    if ("oneOf" in schema) {
        return "oneOf";
    }

    if ("allOf" in schema) {
        return "allOf";
    }

    if ("properties" in schema || ("type" in schema && schema.type === "object")) {
        let label = "{} object";
        if ("x-label" in schema && schema["x-label"]) {
            label = `{} ${schema["x-label"]}`;
        }
        return label;
    }

    if (schema.type === "array" || "items" in schema) {
        return `array<${jsonSchemaType(schema.items!)}>`;
    }

    if (!schema.type) {
        return "unknown";
    }

    if (Array.isArray(schema.type)) {
        return schema.type.join(" | ");
    }

    if (schema.type === "string") {
        return schema.format ? `${schema.type} (${schema.format})` : schema.type;
    }

    return schema.type;
}
