/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import {
    IAttachment,
    IAttachmentIntegrationCatalog,
    IIntegrationsApi,
} from "@library/features/discussions/integrations/Integrations.types";
import { JsonSchema } from "@vanilla/json-schema-forms";

const GET_CATALOG_ENDPOINT = "/attachments/catalog";
const GET_SCHEMA_ENDPOINT = "/attachments/schema";
const POST_ATTACHMENT_ENDPOINT = "/attachments";

export const IntegrationsApi: IIntegrationsApi = {
    getIntegrationsCatalog: async function () {
        const response = await apiv2.get<IAttachmentIntegrationCatalog>(GET_CATALOG_ENDPOINT);
        return response.data;
    },
    getAttachmentSchema: async function (params) {
        const response = await apiv2.get<JsonSchema>(GET_SCHEMA_ENDPOINT, {
            params,
        });
        return response.data;
    },
    postAttachment: async function (params) {
        const response = await apiv2.post<IAttachment>(POST_ATTACHMENT_ENDPOINT, params);
        return response.data;
    },
};
