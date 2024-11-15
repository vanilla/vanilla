import type { JsonSchema, PartialSchemaDefinition } from "@vanilla/json-schema-forms";

export type OpenApiRef = { $ref: string };

type JsonSchemaOrRef = PartialSchemaDefinition | OpenApiRef;

export interface IOpenApiSpec {
    info?: {
        title?: string;
        description?: string;
        version?: string;
    };
    paths: Record<string, IOpenApiPathSpec>;
    components?: {
        responses?: Record<string, IOpenApiResponseSpec>;
        parameters?: Record<string, IOpenApiParameterSpec>;
        schemas?: Record<string, JsonSchema>;
    };
    tags?: OpenApiTagDetail[];
}

type OpenApiTagDetail = {
    name: string;
    description: string;
};

export type OpenApiPathMethod = "get" | "post" | "put" | "delete" | "options" | "head" | "patch";

type IOpenApiPathSpec = Partial<Record<OpenApiPathMethod, IOpenApiMethodSpec>> & {
    parameters?: Array<IOpenApiParameterSpec | OpenApiRef>;
};

type IOpenApiMethodSpec = {
    summary?: string;
    description?: string;
    operationId?: string;
    deprecated?: boolean;
    parameters?: Array<IOpenApiParameterSpec | OpenApiRef>;
    responses: Record<string, IOpenApiResponseSpec | OpenApiRef>;
    requestBody?:
        | {
              description?: string;
              content: Record<string, IOpenApicontentSpec>;
          }
        | OpenApiRef;
    tags?: string[];
};

export type IOpenApiProcessedEndpoint = IOpenApiMethodSpec & {
    method: string;
    path: string;
    hash: string;
};

export type IOpenApiParameterSpec = {
    name: string;
    in: "query" | "header" | "path" | "cookie";
    required?: boolean;
    description?: string;
    schema: JsonSchemaOrRef;
};

type ContentType = "application/json" | "application/xml" | "text/plain" | "text/html";

type IOpenApiResponseSpec = {
    description?: string;
    content?: Record<ContentType, IOpenApicontentSpec>;
};

type IOpenApicontentSpec = {
    schema?: JsonSchemaOrRef;
};
