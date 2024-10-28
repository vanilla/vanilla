/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { BottomChevronIcon, TopChevronIcon } from "@library/icons/common";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Row } from "@library/layout/Row";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { openApiClasses } from "@library/openapi/OpenApiClasses";
import { OpenApiContextProvider, useOpenApiContext } from "@library/openapi/OpenApiContext";
import { EndpointContent } from "@library/openapi/OpenApiEndpointContent";
import { OpenApiEndpointLabel } from "@library/openapi/OpenApiEndpointHeader";
import { EndpointResponses } from "@library/openapi/OpenApiEndpointResponses";
import { OpenApiText } from "@library/openapi/OpenApiText";
import { TryItContextProvider, useTryItContext, type IOpenApiRoute } from "@library/openapi/OpenApiTryIt.context";
import type { IOpenApiParameterSpec, IOpenApiProcessedEndpoint, IOpenApiSpec } from "@library/openapi/OpenApiTypes";
import { openApiMethodColor, resolveOpenApiRef, useResolvedOpenApiSchema } from "@library/openapi/OpenApiUtils";
import { PropertySchema } from "@library/openapi/PropertySchema";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { createLoadableComponent } from "@vanilla/react-utils";
import { useState } from "react";

interface IProps {
    spec: IOpenApiSpec;
    tryItEnabled?: boolean;
    initialFocusedRoute?: IOpenApiRoute;
}

const TryItModal = createLoadableComponent({
    loadFunction: () => import("./OpenApiTryIt").then((mod) => mod.OpenApiTryIt),
    fallback: () => null,
});

export function OpenApiViewerImpl(props: IProps) {
    const [tryItRoute, setTryItRoute] = useState<IOpenApiRoute | null>(null);

    return (
        <OpenApiContextProvider spec={props.spec} initialFocusedRoute={props.initialFocusedRoute}>
            <TryItContextProvider
                value={{
                    route: tryItRoute,
                    setRoute: setTryItRoute,
                    enabled: props.tryItEnabled ?? false,
                }}
            >
                <OpenApiTags />
                {tryItRoute !== null && <TryItModal />}
            </TryItContextProvider>
        </OpenApiContextProvider>
    );
}

function OpenApiTags() {
    const { spec, processedEndpointsByTag } = useOpenApiContext();

    return (
        <div>
            {Object.entries(processedEndpointsByTag).map(([tag, endpoints]) => {
                return <TaggedEndpoints key={tag} tag={tag} endpoints={endpoints} />;
            })}
        </div>
    );
}

function TaggedEndpoints(props: { tag: string; endpoints: IOpenApiProcessedEndpoint[] }) {
    const { tag, endpoints } = props;
    const { spec, expandedTags, toggleTagExpansion } = useOpenApiContext();
    const isExpanded = expandedTags.includes(tag);

    const tagDescription = spec.tags?.find((t) => t.name === tag)?.description;
    const classes = openApiClasses();
    return (
        <div className={classes.tagGroup} data-id={`/${encodeURIComponent(tag)}`}>
            <PageHeadingBox title={tag} description={tagDescription} />
            <EndpointSummary tag={tag} endpoints={endpoints} />
            {isExpanded &&
                endpoints.map((endpoint) => (
                    <EndpointDetails key={`${endpoint.method}-${endpoint.path}`} endpoint={endpoint} />
                ))}
        </div>
    );
}

function EndpointSummary(props: { tag: string; endpoints: IOpenApiProcessedEndpoint[] }) {
    const { spec, expandedTags, toggleTagExpansion, focusRoute } = useOpenApiContext();
    const isExpanded = expandedTags.includes(props.tag);
    const classes = openApiClasses();
    return (
        <div className={classes.endpointContainer}>
            {/* Not using a real heading because it fights with user content styles too much. */}
            <div className={cx(classes.endpointSummaryTitle)} role="heading" aria-level={3}>
                <span>{t("Endpoint Summary")}</span>
                <Button
                    buttonType={ButtonTypes.TEXT}
                    onClick={() => {
                        toggleTagExpansion(props.tag);
                    }}
                >
                    {isExpanded ? t("Collapse") : t("Expand")}
                </Button>
            </div>
            <ul className={classes.endpointSummaryList}>
                {props.endpoints.map((endpoint, i) => (
                    <li key={i} className={classes.endpointSummaryItem}>
                        <ToolTip label={endpoint.description ?? endpoint.summary}>
                            <Button
                                className={classes.endpointSummaryItemButton}
                                buttonType={ButtonTypes.CUSTOM}
                                onClick={() => {
                                    focusRoute(endpoint);
                                }}
                            >
                                <span
                                    style={{ color: openApiMethodColor(endpoint.method) }}
                                    className={classes.endpointSummaryMethod}
                                >
                                    {endpoint.method.toUpperCase()}
                                </span>{" "}
                                {endpoint.path}
                            </Button>
                        </ToolTip>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function EndpointDetails(props: { endpoint: IOpenApiProcessedEndpoint }) {
    const { endpoint } = props;
    const openApiContext = useOpenApiContext();
    const tryItContext = useTryItContext();
    const classes = openApiClasses();
    const isExpanded = !!openApiContext.expandedRoutes.find(
        (route) => route.method === endpoint.method && route.path === endpoint.path,
    );
    const isFocused =
        openApiContext.focusedRoute?.method === endpoint.method && openApiContext.focusedRoute?.path === endpoint.path;
    function toggleCollapsed() {
        openApiContext.toggleRoute({ path: endpoint.path, method: endpoint.method });
    }

    const scrollOffset = useScrollOffset();

    const offset = scrollOffset.rawScrollOffset ?? 80;

    return (
        <div
            className={cx(classes.endpointContainer, classes.detailContainer, {
                isFocused,
            })}
            id={endpoint.hash}
            style={{
                scrollMarginTop: offset,
            }}
        >
            <div className={cx(classes.endpointDetailRow, classes.endpointHeaderRow)}>
                <div
                    style={{ flex: 1, cursor: "pointer" }}
                    onClick={() => {
                        toggleCollapsed();
                    }}
                >
                    <OpenApiEndpointLabel endpoint={endpoint} />
                </div>
                <Row gap={4}>
                    {tryItContext.enabled && (
                        <Button
                            buttonType={ButtonTypes.STANDARD}
                            onClick={() => {
                                tryItContext.setRoute({ path: endpoint.path, method: endpoint.method });
                            }}
                        >
                            Try It
                        </Button>
                    )}
                    <Button
                        onClick={() => {
                            toggleCollapsed();
                        }}
                        buttonType={ButtonTypes.ICON}
                        title={isExpanded ? t("Collapse") : t("Expand")}
                    >
                        {isExpanded ? <TopChevronIcon /> : <BottomChevronIcon />}
                    </Button>
                </Row>
            </div>
            {isExpanded && (
                <>
                    <EndpointParameters endpoint={endpoint} />
                    <EndpointBodies endpoint={endpoint} />
                    <EndpointRow title={t("Responses")}>
                        <EndpointResponses endpoint={endpoint} />
                    </EndpointRow>
                </>
            )}
        </div>
    );
}

function EndpointRow(props: { title: React.ReactNode; children: React.ReactNode }) {
    const classes = openApiClasses();
    return (
        <div className={classes.endpointDetailRow}>
            <label className={classes.endpointDetailRowLabel}>{props.title}</label>
            <div>{props.children}</div>
        </div>
    );
}

function EndpointBodies(props: { endpoint: IOpenApiProcessedEndpoint }) {
    const { endpoint } = props;
    const { spec } = useOpenApiContext();
    let requestBody = useResolvedOpenApiSchema(endpoint.requestBody);

    if (!requestBody) {
        return <></>;
    }

    const contentEntries = Object.entries(requestBody.content).filter(([_, content]) => content.schema);
    if (contentEntries.length === 0) {
        return <></>;
    }

    return (
        <EndpointRow title={t("Request Body")}>
            <OpenApiText content={requestBody.description} />
            {contentEntries.map(([contentType, content]) => {
                return (
                    <EndpointContent
                        key={contentType}
                        contentType={contentType}
                        schema={resolveOpenApiRef(spec, content.schema as any)}
                    />
                );
            })}
        </EndpointRow>
    );
}

function EndpointParameters(props: { endpoint: IOpenApiProcessedEndpoint }) {
    const { endpoint } = props;
    const { parameters } = endpoint;
    const { spec } = useOpenApiContext();

    if (!parameters || parameters.length === 0) {
        return <></>;
    }

    const resolvedParams = parameters.map((param) => resolveOpenApiRef(spec, param));

    const pathParams = resolvedParams.filter((param) => param.in === "path");
    const cookieParams = resolvedParams.filter((param) => param.in === "cookie");
    const headerParams = resolvedParams.filter((param) => param.in === "header");
    const queryParams = resolvedParams.filter((param) => param.in === "query");

    return (
        <>
            <EndpointParameterSubset label={t("Path Parameters")} parameters={pathParams} />
            <EndpointParameterSubset label={t("Query Parameters")} parameters={queryParams} />
            <EndpointParameterSubset label={t("Header Parameters")} parameters={headerParams} />
            <EndpointParameterSubset label={t("Cookie Parameters")} parameters={cookieParams} />
        </>
    );
}

function EndpointParameterSubset(props: { label: string; parameters: IOpenApiParameterSpec[] }) {
    const { label, parameters } = props;
    const { spec } = useOpenApiContext();
    if (parameters.length === 0) {
        return <></>;
    }

    return (
        <EndpointRow title={label}>
            {parameters.map((parameter, i) => {
                return (
                    <PropertySchema
                        key={i}
                        schema={{ description: parameter.description, ...parameter.schema }}
                        propertyName={parameter.name}
                        required={parameter.required}
                    />
                );
            })}
        </EndpointRow>
    );
}
