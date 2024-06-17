/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    IAttachment,
    IAttachmentIntegration,
    IAttachmentIntegrationCatalog,
    IIntegrationsApi,
} from "@library/features/discussions/integrations/Integrations.types";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { PropsWithChildren } from "react";
import { fn } from "@storybook/test";
import {
    AttachmentIntegrationsApiContextProvider,
    AttachmentIntegrationsContextProvider,
} from "../Integrations.context";

export const FAKE_WRITEABLE_INTEGRATION: IAttachmentIntegration = {
    name: "fakeIntegration",
    label: "Fake Integration for Discussions And Comments",
    attachmentType: "fakeIntegration",
    recordTypes: ["discussion", "comment"],
    writeableContentScope: "all",
    submitButton: "Create Task",
    externalIDLabel: "Task #",
    title: "Todo List Service - Task",
    logoIcon: "meta-external",
};

export const FAKE_ESCALATE_OWN_CONTENT_ONLY_INTEGRATION: IAttachmentIntegration = {
    name: "fakeEscalateOwnContentOnlyIntegration",
    label: "Fake Escalate Own Content Only Integration for Discussions",
    attachmentType: "fakeEscalateOwnContentOnlyIntegration",
    recordTypes: ["discussion"],
    writeableContentScope: "own",
    submitButton: "Create Thing",
    externalIDLabel: "Thing #",
    title: "Stuff Service - Thing",
    logoIcon: "meta-external",
};

export const FAKE_READ_ONLY_INTEGRATION: IAttachmentIntegration = {
    name: "fakeReadOnlyIntegration",
    label: "Fake Read-Only Integration for Discussions",
    attachmentType: "fakeReadOnlyIntegration",
    recordTypes: ["discussion"],
    writeableContentScope: "none",
    submitButton: "Create Item",
    externalIDLabel: "Item #",
    title: "Read-only Service - Item",
    logoIcon: "meta-external",
};

export const FAKE_INTEGRATIONS_CATALOG: IAttachmentIntegrationCatalog = {
    [FAKE_WRITEABLE_INTEGRATION.attachmentType]: FAKE_WRITEABLE_INTEGRATION,
    [FAKE_ESCALATE_OWN_CONTENT_ONLY_INTEGRATION.attachmentType]: FAKE_ESCALATE_OWN_CONTENT_ONLY_INTEGRATION,
    [FAKE_READ_ONLY_INTEGRATION.attachmentType]: FAKE_READ_ONLY_INTEGRATION,
};

export const FAKE_INTEGRATION_SCHEMAS: Record<string, JsonSchema> = {
    [FAKE_WRITEABLE_INTEGRATION.attachmentType]: {
        type: "object",
        properties: {
            title: {
                type: "string",
                title: "Title",
                default: "A new task",
                "x-control": {
                    label: "Title",
                    inputType: "textBox",
                },
            },
            description: {
                type: "string",
                title: "Description",
                default: "A fake description",
                "x-control": {
                    label: "Description",
                    inputType: "textBox",
                    type: "textarea",
                },
            },
        },
        required: ["title"],
    },
    [FAKE_ESCALATE_OWN_CONTENT_ONLY_INTEGRATION.attachmentType]: {
        type: "object",
        properties: {
            title: {
                type: "string",
                title: "Title",
                default: "A new thing",
                "x-control": {
                    label: "Title",
                    inputType: "textBox",
                },
            },
        },
        required: ["title"],
    },
};

export const FAKE_ATTACHMENT: IAttachment = {
    attachmentID: 1,
    attachmentType: FAKE_WRITEABLE_INTEGRATION["attachmentType"],
    recordType: "discussion",
    recordID: `${9999999}`,
    state: "Completed",
    sourceID: `${123456}`,
    sourceUrl: "#",
    dateInserted: "2020-10-06T15:30:44+00:00",
    metadata: [
        {
            labelCode: "Name",
            value: "This content is generated by users on the site. You can't update it here.",
        },
        {
            labelCode: "Title",
            value: "This content is generated by users on the site. You can't update it here.",
        },
        {
            labelCode: "Company",
            value: "This content is generated by users on the site. You can't update it here.",
        },
        {
            labelCode: "Favourite Color",
            value: "This content is generated by users on the site. You can't update it here.",
        },
        {
            labelCode: "More Information",
            value: "This content is generated by users on the site. You can't update it here.",
        },
        {
            labelCode: "Subcontractor",
            value: "This content is generated by users on the site. You can't update it here.",
        },
    ],
};

export const FAKE_API: IIntegrationsApi = {
    getIntegrationsCatalog: async () => FAKE_INTEGRATIONS_CATALOG,
    getAttachmentSchema: async (params) => FAKE_INTEGRATION_SCHEMAS[params.attachmentType],
    postAttachment: async (params) => ({} as any),
    refreshAttachments: async (params) => [],
};

export const queryClient = new QueryClient({
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
        <QueryClientProvider client={queryClient}>
            <AttachmentIntegrationsApiContextProvider api={mockApi}>
                <AttachmentIntegrationsContextProvider>{children}</AttachmentIntegrationsContextProvider>
            </AttachmentIntegrationsApiContextProvider>
        </QueryClientProvider>
    );
}
