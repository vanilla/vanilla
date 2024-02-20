/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    IAttachmentIntegration,
    IAttachmentIntegrationCatalog,
    IIntegrationsApi,
} from "@library/features/discussions/integrations/Integrations.types";
import { JsonSchema } from "@vanilla/json-schema-forms";

export const FAKE_INTEGRATION: IAttachmentIntegration = {
    label: "Fake Integration for Discussion",
    attachmentType: "fakeIntegration",
    recordTypes: ["discussion", "comment"],
    submitButton: "Create Task",
};

export const FAKE_INTEGRATIONS_CATALOG: IAttachmentIntegrationCatalog = {
    [FAKE_INTEGRATION.attachmentType]: FAKE_INTEGRATION,
};

export const FAKE_INTEGRATION_SCHEMAS: Record<string, JsonSchema> = {
    [FAKE_INTEGRATION.attachmentType]: {
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
};

export function createMockApi(): IIntegrationsApi {
    return {
        getIntegrationsCatalog: jest.fn(async () => FAKE_INTEGRATIONS_CATALOG),
        getAttachmentSchema: jest.fn(async (params) => FAKE_INTEGRATION_SCHEMAS[params.attachmentType]),
        postAttachment: jest.fn(async (params) => ({} as any)),
    };
}
