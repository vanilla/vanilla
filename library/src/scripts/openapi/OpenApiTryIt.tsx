/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Table } from "@dashboard/components/Table";
import { DashboardFormControlHeadingSection } from "@dashboard/forms/DashboardFormControlHeadingSection";
import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { cx } from "@emotion/css";
import apiv2 from "@library/apiv2";
import UserContent from "@library/content/UserContent";
import { userContentClasses } from "@library/content/UserContent.styles";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { type IFormControl, type JsonSchema, type JSONSchemaType } from "@library/json-schema-forms";
import { Row } from "@library/layout/Row";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { TokenItem } from "@library/metas/TokenItem";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useOpenApiContext } from "@library/openapi/OpenApiContext";
import { EndpointResponses } from "@library/openapi/OpenApiEndpointResponses";
import { OpenApiEnumList } from "@library/openapi/OpenApiEnumList";
import { useTryItContext, type IOpenApiRoute } from "@library/openapi/OpenApiTryIt.context";
import type { IOpenApiProcessedEndpoint, IOpenApiSpec } from "@library/openapi/OpenApiTypes";
import {
    openApiMethodColor,
    resolveOpenApiRef,
    resolveOpenApiRefRecursively,
    sortRequiredSchemaPropertiesFirst,
} from "@library/openapi/OpenApiUtils";
import { PropertySchemaLabel } from "@library/openapi/ProperySchemaLabel";
import { openApiTryItClasses as classes } from "@library/openapi/OpenApiTryIt.classes";
import TextEditor from "@library/textEditor/TextEditor";
import { ToolTip } from "@library/toolTip/ToolTip";
import { assetUrl, siteUrl, t } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import { escapeHTML } from "@vanilla/dom-utils";
import { Icon } from "@vanilla/icons";
import { useIsMounted } from "@vanilla/react-utils";
import { notEmpty } from "@vanilla/utils";
import { type AxiosResponse, type AxiosResponseHeaders, type Method } from "axios";
import qs from "qs";
import { createContext, useCallback, useContext, useMemo, useRef, useState } from "react";
import ReactMarkdown from "react-markdown";

export function OpenApiTryIt() {
    const context = useTryItContext();
    const { spec } = useOpenApiContext();

    const submitContext = useApiSubmit();

    const formRef = useRef<HTMLFormElement | null>(null);
    const [forceResponseSchema, setForceResponseSchema] = useState(false);

    if (!context.enabled) {
        return <></>;
    }

    if (!context.enabled) {
        return <></>;
    }

    if (!context.route) {
        return <></>;
    }

    const routeSpec = spec.paths[context.route.path][context.route.method.toLowerCase()];
    if (!routeSpec) {
        return <></>;
    }

    let pathParams = (spec.paths[context.route.path] as any).parameters;
    if (pathParams && !Array.isArray(pathParams)) {
        pathParams = Object.values(pathParams);
    }

    const endpoint: IOpenApiProcessedEndpoint = {
        ...routeSpec,
        parameters: [...(routeSpec.parameters ?? []), ...(pathParams ?? [])],
        method: context.route.method,
        path: context.route.path,
    };

    return (
        <Modal
            isVisible={!!context.route}
            exitHandler={() => {
                context.setRoute(null);
            }}
            size={ModalSizes.XXL}
        >
            <form
                className={classes.form}
                ref={formRef}
                onSubmit={(e) => {
                    e.preventDefault();
                    if (!formRef.current?.checkValidity()) {
                        formRef.current?.reportValidity();
                    }
                    const actualPath = replacePathParameters(endpoint.path, submitContext.pathValue);
                    submitContext.submitMutation.mutate({
                        method: endpoint.method,
                        path: actualPath,
                        queryParams: submitContext.queryValue,
                        body: submitContext.bodyValue,
                    });
                    setForceResponseSchema(false);
                }}
            >
                <EndpointHeader submitContext={submitContext} route={endpoint} />
                <div className={classes.split}>
                    <div className={classes.splitRequest}>
                        <ErrorBoundary isFixed={false}>
                            <Form endpoint={endpoint} submitContext={submitContext} />
                        </ErrorBoundary>
                    </div>
                    <div className={classes.splitResponse}>
                        <DashboardFormSubheading
                            hasBackground={true}
                            actions={
                                <Button
                                    buttonType={ButtonTypes.STANDARD}
                                    onClick={() => {
                                        setForceResponseSchema(true);
                                    }}
                                >
                                    {t("Show Schema")}
                                </Button>
                            }
                        >
                            {t("Response")}
                        </DashboardFormSubheading>
                        {submitContext.submitMutation.data && !forceResponseSchema ? (
                            <ActualResponse response={submitContext.submitMutation.data} />
                        ) : (
                            <EndpointResponses expandDefault={true} endpoint={endpoint} />
                        )}
                    </div>
                </div>
            </form>
        </Modal>
    );
}

function ActualResponse(props: { response: AxiosResponse }) {
    const { response } = props;
    return (
        <div>
            <div style={{ marginBottom: 12, marginTop: 4 }}>
                <strong>Status: </strong>
                {response.status}
            </div>
            <h4 style={{ marginTop: 4, marginBottom: 12 }}>{t("Response")}</h4>
            <ResponseContent content={response.data ?? "No Content"} />
            <ResponseHeaders headers={response.headers} />
        </div>
    );
}

function ResponseHeaders(props: { headers: AxiosResponseHeaders }) {
    return (
        <div>
            <Table
                truncateCells={false}
                headerClassNames={classes.tableHeader}
                cellClassNames={classes.cell}
                data={Object.entries(props.headers).map(([header, value]) => {
                    const values = Array.isArray(value) ? value : [value];
                    return {
                        header: header,
                        value: (
                            <Row gap={4} wrap={true} align={"start"} width={"100%"}>
                                {values.map((value, i) => {
                                    return <TokenItem key={i}>{value}</TokenItem>;
                                })}
                            </Row>
                        ),
                    };
                })}
            />
        </div>
    );
}

function ResponseContent(props: { content: any }) {
    const content = props.content;
    return (
        <UserContent
            content={`<pre class="code codeBlock">${
                typeof content !== "string" ? escapeHTML(JSON.stringify(content, null, 2)) : escapeHTML(content)
            }</pre>`}
        />
    );
}

function useApiSubmit() {
    const [pathValue, setPathValue] = useState<any>({});
    const [queryValue, setQueryValue] = useState<any>({});
    const [bodyValue, setBodyValue] = useState<any>({});
    const submitMutation = useMutation({
        mutationFn: async (options: { method: string; path: string; body?: any; queryParams?: any }) => {
            try {
                const response = await apiv2.request({
                    method: options.method as Method,
                    url: options.path,
                    headers: {
                        "X-Requested-With": "vanilla",
                    },
                    data: options.body,
                    params: options.queryParams,
                    paramsSerializer: (params) => qs.stringify(params),
                });
                return response;
            } catch (err) {
                return err.response;
            }
        },
        retry: false,
    });
    return {
        pathValue,
        setPathValue,
        queryValue,
        setQueryValue,
        bodyValue,
        setBodyValue,
        submitMutation,
    };
}

function replacePathParameters(path: string, pathParameters: AnyObject): string {
    let result = path;
    for (const [key, val] of Object.entries(pathParameters)) {
        if (val) {
            result = result.replace(`:${key}`, val);
            result = result.replace(`{${key}}`, val);
        }
    }
    return result;
}

function useCopier() {
    const [wasCopied, _setWasCopied] = useState(false);
    const isMounted = useIsMounted();

    const currentTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    const setWasCopied = () => {
        if (currentTimeoutRef.current) {
            clearTimeout(currentTimeoutRef.current);
        }
        _setWasCopied(true);
        currentTimeoutRef.current = setTimeout(() => {
            if (isMounted()) {
                _setWasCopied(false);
            }
        }, 3000);
    };

    function copyValue(value: string) {
        navigator.clipboard.writeText(value).then(() => {
            if (isMounted()) {
                setWasCopied();
            }
        });
    }

    return { wasCopied, copyValue };
}

function CopyAsDropdown(props: { route: IOpenApiRoute; submitContext: ReturnType<typeof useApiSubmit> }) {
    const { route, submitContext } = props;

    const baseUrl = siteUrl("/api/v2");
    const actualPath = replacePathParameters(route.path, submitContext.pathValue);
    let fullUrl = baseUrl + actualPath;
    if (Object.keys(submitContext.queryValue).length > 0) {
        fullUrl += "?" + qs.stringify(submitContext.queryValue, {});
    }

    const curlCopier = useCopier();
    const fetchCopier = useCopier();

    return (
        <DropDown
            flyoutType={FlyoutType.LIST}
            buttonContents={t("Copy as...")}
            buttonType={ButtonTypes.STANDARD}
            buttonClassName={classes.headerButton}
        >
            <DropDownItemButton
                onClick={() => {
                    let curlCommand = `curl --request ${route.method.toUpperCase()} \\
--url ${fullUrl} \\
--header "Authorization: Bearer $VANILLA_AUTH_TOKEN" \\
--header 'X-Requested-With: vanilla' \\
--header 'Content-Type: application/json'`;

                    if (Object.keys(submitContext.bodyValue).length > 0) {
                        let serializedBody = JSON.stringify(submitContext.bodyValue, null, 2);
                        serializedBody = serializedBody.replace("'", "'\\''");
                        curlCommand += ` \\
--data '${serializedBody}'`;
                    }

                    curlCopier.copyValue(curlCommand);
                }}
            >
                {curlCopier.wasCopied ? t("Copied to Clipboard") : t("Copy as CURL")}
            </DropDownItemButton>
            <DropDownItemButton
                onClick={() => {
                    let fetchBase = "";
                    if (Object.keys(submitContext.bodyValue).length > 0) {
                        let serializedBody = JSON.stringify(submitContext.bodyValue, null, 4);
                        fetchBase = `
fetch("${fullUrl}", {
    method: "${route.method}",
    body: ${serializedBody},
    headers: {
        "X-Requested-With": "vanilla",
        Authorization: "Bearer VANILLA_ACCESS_TOKEN",
    },
})`;
                    } else {
                        fetchBase = `
fetch("${fullUrl}", {
    method: "${route.method}",
    headers: {
        "X-Requested-With": "vanilla",
        Authorization: "Bearer VANILLA_ACCESS_TOKEN",
    },
})`;
                    }

                    let fetchSuffix = `
    .then((response) => {
        if (response.ok) {
            return response;
        } else {
            // API responses come back as JSON.
            return response.json().then((json) => Promise.reject(json));
        }
    })
    .then((response) => response.json())
    .then((json) => console.log(json))
    .catch((jsonError) => console.error(jsonError));`;
                    const fetchCommand = fetchBase + "\n" + fetchSuffix;

                    fetchCopier.copyValue(fetchCommand);
                }}
            >
                {fetchCopier.wasCopied ? t("Copied to Clipboard") : t("Copy as Fetch (Javascript)")}
            </DropDownItemButton>
        </DropDown>
    );
}

function EndpointHeader(props: { route: IOpenApiRoute; submitContext: ReturnType<typeof useApiSubmit> }) {
    const { route, submitContext } = props;
    const { submitMutation } = submitContext;
    const baseUrl = siteUrl("/api/v2");
    return (
        <div className={classes.header}>
            <div className={classes.headerInput}>
                <span className={classes.headerMethod} style={{ color: openApiMethodColor(route.method) }}>
                    {route.method}
                </span>
                <span className={classes.headerSep}></span>
                <span className={classes.baseUrl}>{baseUrl}</span>
                <span>{route.path}</span>
                <span className={classes.spacer}></span>
                <CopyAsDropdown submitContext={submitContext} route={route} />
                <Button
                    disabled={submitMutation.isLoading}
                    type={"submit"}
                    buttonType={ButtonTypes.PRIMARY}
                    className={classes.headerButton}
                >
                    <Icon size={"compact"} icon={"data-send"} />
                    {submitMutation.isLoading ? <ButtonLoader /> : t("Send")}
                </Button>
            </div>
        </div>
    );
}

function Form(props: { endpoint: IOpenApiProcessedEndpoint; submitContext: ReturnType<typeof useApiSubmit> }) {
    const { endpoint, submitContext } = props;

    const [useRawJson, setUseRawJson] = useState(false);

    const pathSchema = useParameterBasedSchema(endpoint, "path");
    const querySchema = useParameterBasedSchema(endpoint, "query");
    const bodySchema = useRequestBodySchema(endpoint);
    const hasBodySchema = Object.keys(bodySchema.properties ?? {}).length > 0;
    const hasQuerySchema = Object.keys(querySchema.properties ?? {}).length > 0;
    const hasPathSchema = Object.keys(pathSchema.properties ?? {}).length > 0;

    const { spec } = useOpenApiContext();
    const rawBodySchema = useMemo(() => {
        const resolved = resolveOpenApiRefRecursively(
            spec,
            resolveOpenApiRefRecursively(spec, endpoint.requestBody)?.content?.["application/json"]?.["schema"],
        );

        return sortRequiredSchemaPropertiesFirst(resolved as any);
    }, [endpoint]);
    const [rawJsonValue, _setRawJsonValue] = useState("");
    const [isRawJsonDirty, setIsRawJsonDirty] = useState(false);
    const [jsonError, setJsonError] = useState<string | null>(null);
    const setRawJsonValue = useCallback(
        (value: string) => {
            _setRawJsonValue(value);
            setIsRawJsonDirty(true);
            setJsonError(null);
        },
        [_setRawJsonValue, setJsonError],
    );

    function toggleRawJson() {
        if (!useRawJson) {
            // As we transition we need to set this to be either the form value or the default value.

            let valueToSerialize = {};
            if (Object.keys(submitContext.bodyValue).length > 0) {
                // The form was already started to be filled.
                valueToSerialize = submitContext.bodyValue;
            } else {
                valueToSerialize = schemaDefaultValue(rawBodySchema as any);
            }
            setRawJsonValue(JSON.stringify(valueToSerialize, null, 2));
            setIsRawJsonDirty(false);
            setUseRawJson(true);
        } else {
            if (!isRawJsonDirty) {
                // No need to try and convert anything. It wasn't modified.
                setUseRawJson(false);
                return;
            }

            // We are switching away, let's decode the JSON.
            try {
                const newValue = JSON.parse(rawJsonValue);
                submitContext.setBodyValue(newValue);
                setUseRawJson(false);
                setIsRawJsonDirty(false);
            } catch (err) {
                setJsonError("Invalid JSON in the editor.");
            }
        }
    }

    return (
        <ul>
            <DashboardFormSubheading hasBackground={true}>{t("Path Parameters")}</DashboardFormSubheading>
            {!hasPathSchema && (
                <p className={classes.emptyFormSection}>No path parameters are supported for this endpoint.</p>
            )}
            {hasPathSchema && (
                <DashboardSchemaForm
                    FormSection={DashboardFormControlHeadingSection}
                    instance={submitContext.pathValue}
                    onChange={submitContext.setPathValue}
                    schema={pathSchema}
                />
            )}
            <DashboardFormSubheading hasBackground={true}>{t("Query Parameters")}</DashboardFormSubheading>
            {!hasQuerySchema && (
                <p className={classes.emptyFormSection}>No query parameters are supported for this endpoint.</p>
            )}
            {hasQuerySchema && (
                <DashboardSchemaForm
                    FormSection={DashboardFormControlHeadingSection}
                    instance={submitContext.queryValue}
                    onChange={submitContext.setQueryValue}
                    schema={querySchema}
                />
            )}
            <DashboardFormSubheading
                hasBackground={true}
                actions={
                    <>
                        {hasBodySchema && !useRawJson && (
                            <Button
                                buttonType={ButtonTypes.STANDARD}
                                onClick={() => {
                                    submitContext.setBodyValue({});
                                }}
                            >
                                {t("Clear")}
                            </Button>
                        )}
                        {hasBodySchema && (
                            <ToolTip
                                label={
                                    jsonError
                                        ? jsonError
                                        : useRawJson
                                        ? t("Use a visual form to control the body.")
                                        : t("Use a text editor to edit your body as JSON.")
                                }
                            >
                                <span>
                                    <Button
                                        buttonType={ButtonTypes.STANDARD}
                                        disabled={jsonError != null}
                                        onClick={() => {
                                            toggleRawJson();
                                        }}
                                    >
                                        {useRawJson ? t("Show Form") : t("Show Editor")}
                                    </Button>
                                </span>
                            </ToolTip>
                        )}
                    </>
                }
            >
                {t("Request Body")}
            </DashboardFormSubheading>
            {!hasBodySchema && (
                <p className={classes.emptyFormSection}>{t("Request body is not supported for this endpoint.")}</p>
            )}
            {useRawJson ? (
                <div>
                    <TextEditor
                        jsonSchema={rawBodySchema as any}
                        noPadding={true}
                        language={"json"}
                        value={rawJsonValue}
                        onChange={(value) => {
                            setRawJsonValue(value ?? "");
                        }}
                    />
                </div>
            ) : (
                <TryItFormContext.Provider
                    value={{
                        setUseRawJson: () => {
                            setUseRawJson(true);
                        },
                    }}
                >
                    <DashboardSchemaForm
                        FormSection={DashboardFormControlHeadingSection}
                        instance={submitContext.bodyValue}
                        onChange={submitContext.setBodyValue}
                        schema={bodySchema}
                    />
                </TryItFormContext.Provider>
            )}
        </ul>
    );
}

function CustomElementRequiresRawJson() {
    const context = useContext(TryItFormContext);

    return (
        <Button
            buttonType={ButtonTypes.STANDARD}
            onClick={() => {
                context.setUseRawJson();
            }}
        >
            {t("Edit in JSON editor.")}
        </Button>
    );
}

interface ITryItFormContext {
    setUseRawJson(): void;
}
const TryItFormContext = createContext<ITryItFormContext>({
    setUseRawJson() {},
});

function useParameterBasedSchema(endpoint: IOpenApiProcessedEndpoint, paramIn: "path" | "query"): JsonSchema {
    const { spec } = useOpenApiContext();

    let resolvedParams = endpoint.parameters?.map((param) => resolveOpenApiRef(spec, param)) ?? [];
    resolvedParams = resolvedParams.map((param) => ({ ...param, schema: resolveOpenApiRef(spec, param.schema) }));
    const filteredParams = resolvedParams.filter((param) => param.in === paramIn).filter(notEmpty);
    const pathSchema = {
        type: "object",
        required: filteredParams.filter((param) => param.required).map((param) => param.name),
        properties: Object.fromEntries(
            filteredParams.map((param) => [param.name, { ...param.schema, description: param.description }]) ?? [],
        ),
    };

    return objectSchemaToSchemaForm(spec, pathSchema) ?? pathSchema;
}

function useRequestBodySchema(endpoint: IOpenApiProcessedEndpoint): JsonSchema {
    const { spec } = useOpenApiContext();

    return useMemo(() => {
        const resolvedRequestBody = resolveOpenApiRef(spec, endpoint.requestBody);
        const resolvedJsonBodySchema = resolveOpenApiRef(spec, resolvedRequestBody?.content["application/json"].schema);
        const schema = objectSchemaToSchemaForm(spec, resolvedJsonBodySchema as any) ?? {
            type: "object",
            properties: {},
        };
        return sortRequiredSchemaPropertiesFirst(schema);
    }, [spec, endpoint]);
}

function resolveAllOf(spec: IOpenApiSpec, schema?: JSONSchemaType): JSONSchemaType | undefined {
    if (!schema) {
        return schema;
    }

    if ("allOf" in schema) {
        const allOf = schema.allOf.map((val) => resolveOpenApiRef(spec, val));

        return {
            type: "object",
            properties: Object.fromEntries(allOf.flatMap((val) => Object.entries(val.properties))),
            required: allOf.flatMap((val) => val.required ?? []),
        };
    }

    return schema;
}

function objectSchemaToSchemaForm(spec: IOpenApiSpec, schema?: JsonSchema): JsonSchema | undefined {
    schema = resolveOpenApiRef(spec, schema);
    if (!schema) {
        return schema;
    }

    if ("allOf" in schema) {
        return objectSchemaToSchemaForm(spec, resolveAllOf(spec, schema));
    }

    if ("oneOf" in schema) {
        return {
            ...schema,
            oneOf: schema.oneOf.map((val) => objectSchemaToSchemaForm(spec, val)),
        };
    }

    return {
        ...schema,
        properties: schema.properties
            ? (Object.fromEntries(
                  Object.entries(schema.properties).map(([key, value]) => {
                      value = resolveOpenApiRef(spec, value);

                      if (typeof value === "object" && "properties" in value) {
                          return [
                              key,
                              {
                                  ...value,
                                  properties: objectSchemaToSchemaForm(spec, value.properties),
                              },
                          ];
                      }

                      return [
                          key,
                          {
                              ...value,
                              "x-control": schemaToFormControl(key, value as JsonSchema),
                          },
                      ];
                  }),
              ) as any)
            : undefined,
    };
}

function schemaToFormControl(property: string, schema: JsonSchema): IFormControl {
    const enumVal = schema.enum ?? schema.items?.enum;
    const labelDescription = {
        label: <PropertySchemaLabel propertyName={property} schema={schema} />,
        description: (
            <>
                <ReactMarkdown className={cx(userContentClasses().root, dashboardFormGroupClasses().labelInfo)}>
                    {schema.description}
                </ReactMarkdown>
                {Array.isArray(enumVal) && <OpenApiEnumList enumValues={enumVal} />}
            </>
        ),
    };
    if (schema.type === "string") {
        if (schema.enum) {
            return {
                inputType: "select",
                createable: true,
                options: schema.enum.map((value) => ({ label: value, value })),
                ...labelDescription,
            };
        }

        const result: IFormControl = {
            inputType: "textBox",
            ...labelDescription,
        };
        return result;
    }

    if (schema.type === "number") {
        return {
            inputType: "textBox",
            type: "number",
            ...labelDescription,
        };
    }

    if (schema.type === "boolean") {
        return {
            inputType: "checkBox",
            checkPosition: "right",
            labelType: "justified",
            ...labelDescription,
        };
    }

    const jsonEditorControl: IFormControl = {
        inputType: "custom",
        component: CustomElementRequiresRawJson,
        ...labelDescription,
        labelType: "justified",
    };

    if (schema.type === "array") {
        if (schema.items?.type === "object") {
            return jsonEditorControl;
        } else {
            return {
                inputType: "select",
                createable: true,
                multiple: true,
                options: schema.items?.enum?.map((item: string) => ({ label: item, value: item })),
                ...labelDescription,
            };
        }
    }

    if (schema.type === "object") {
        return jsonEditorControl;
    }

    return {
        inputType: "textBox",
        ...labelDescription,
    };
}

function schemaDefaultValue(schema: JsonSchema): any {
    if (schema.example) {
        return schema.example;
    }

    if (schema.default) {
        return schema.default;
    }

    switch (schema.type) {
        case "object": {
            const results: any[] = [];
            if (schema.properties) {
                for (const [propertyName, subSchema] of Object.entries(schema.properties)) {
                    results.push([propertyName, schemaDefaultValue(subSchema as any)]);
                }
            }
            return Object.fromEntries(results);
        }
        case "array": {
            const result: any[] = [];
            if (schema.items) {
                for (let i = 0; i < 3; i++) {
                    result.push(schemaDefaultValue(schema.items));
                }
            }
            return result;
        }
        case "string":
            if (schema.format === "date-time") {
                return new Date().toISOString();
            }

            if (schema.enum) {
                return schema.enum[0];
            }
            return "some-string";
        case "integer":
            return 524;
        case "boolean":
            return false;
    }

    return undefined;
}
