/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FAKE_API } from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import {
    AttachmentIntegrationsApiContextProvider,
    AttachmentIntegrationsContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import { fn } from "@storybook/test";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { PropsWithChildren } from "react";

export const integrationsFixtureQueryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
        },
    },
});

export const mockApi = {
    getIntegrationsCatalog: fn(FAKE_API.getIntegrationsCatalog),
    getAttachmentSchema: fn(FAKE_API.getAttachmentSchema),
    postAttachment: fn(FAKE_API.postAttachment),
    refreshAttachments: fn(FAKE_API.refreshAttachments),
};

export function IntegrationsTestWrapper({ children }: PropsWithChildren<{}>) {
    return (
        <QueryClientProvider client={integrationsFixtureQueryClient}>
            <AttachmentIntegrationsApiContextProvider api={mockApi}>
                <AttachmentIntegrationsContextProvider>{children}</AttachmentIntegrationsContextProvider>
            </AttachmentIntegrationsApiContextProvider>
        </QueryClientProvider>
    );
}
