/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { OpenApiContextProvider } from "@library/openapi/OpenApiContext";
import { OpenApiTryIt } from "@library/openapi/OpenApiTryIt";
import { TryItContextProvider } from "@library/openapi/OpenApiTryIt.context";
import { OpenApiViewerImpl as OpenApiViewer } from "@library/openapi/OpenApiViewer.loadable";
import { setMeta } from "@library/utility/appUtils";
import { Meta } from "@storybook/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import spec from "./OpenApiSpec.fixture.json";

const meta: Meta = {
    title: "OpenApiViewer",
};

export default meta;

const queryClient = new QueryClient();

export function VanillaSpecTest() {
    return (
        <QueryClientProvider client={queryClient}>
            <OpenApiViewer
                tryItEnabled={false}
                spec={spec as any}
                initialFocusedRoute={{
                    method: "get",
                    path: "/comments",
                }}
            />
        </QueryClientProvider>
    );
}

export function VanillaSpecTryIt() {
    setMeta("context.assetPath", "https://my-site.com/storybook");

    return (
        <QueryClientProvider client={queryClient}>
            <OpenApiContextProvider spec={spec as any}>
                <TryItContextProvider
                    value={{
                        enabled: true,
                        route: {
                            method: "post",
                            path: "/comments",
                        },
                        setRoute() {},
                    }}
                >
                    <OpenApiTryIt />
                </TryItContextProvider>
            </OpenApiContextProvider>
        </QueryClientProvider>
    );
}
