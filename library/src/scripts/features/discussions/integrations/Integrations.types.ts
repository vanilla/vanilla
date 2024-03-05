/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";

export interface IAttachmentIntegration {
    attachmentType: string;
    label: string;
    recordTypes: string[];
    submitButton: string;
}

export type IAttachmentIntegrationCatalog = Record<string, IAttachmentIntegration>;

export interface IAttachment {
    [key: string]: any;
}

export interface IGetAttachmentSchemaParams {
    attachmentType: string;
    recordType: string;
    recordID: RecordID;
}

export interface IPostAttachmentParams {
    // a loose type, since each integration will have its own schema
    [key: string]: any;
}

export interface IIntegrationsApi {
    getIntegrationsCatalog: () => Promise<IAttachmentIntegrationCatalog>;
    getAttachmentSchema: (params: IGetAttachmentSchemaParams) => Promise<JsonSchema>;
    postAttachment: (params: IPostAttachmentParams) => Promise<IAttachment>;
}
