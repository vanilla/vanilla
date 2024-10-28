/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { openApiClasses } from "@library/openapi/OpenApiClasses";
import { useOpenApiContext } from "@library/openapi/OpenApiContext";
import { EndpointContent } from "@library/openapi/OpenApiEndpointContent";
import { OpenApiText } from "@library/openapi/OpenApiText";
import type { IOpenApiProcessedEndpoint } from "@library/openapi/OpenApiTypes";
import { resolveOpenApiRef } from "@library/openapi/OpenApiUtils";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";

export function EndpointResponses(props: { endpoint: IOpenApiProcessedEndpoint; expandDefault?: boolean }) {
    const { endpoint } = props;
    const { spec } = useOpenApiContext();
    const responses = endpoint.responses;
    const classes = openApiClasses();
    return (
        <Tabs
            includeVerticalPadding={false}
            tabType={TabsTypes.BROWSE}
            tabsRootClass={classes.tabRoot}
            tabClass={classes.tabClass}
            data={Object.entries(responses).map(([statusCode, response]) => {
                const resolvedResponse = resolveOpenApiRef(spec, response);
                let tabLabel = `${statusCode}`;

                return {
                    label: tabLabel,
                    contents: (
                        <>
                            {!resolvedResponse.content && (
                                <OpenApiText className={classes.noContent} content={"No Content"} />
                            )}
                            {resolvedResponse.content &&
                                Object.entries(resolvedResponse.content).map(([contentType, content]) => {
                                    return (
                                        <EndpointContent
                                            expandDefault={props.expandDefault}
                                            description={resolvedResponse.description}
                                            key={contentType}
                                            contentType={contentType}
                                            schema={content.schema as any}
                                        />
                                    );
                                })}
                        </>
                    ),
                };
            })}
        />
    );
}
