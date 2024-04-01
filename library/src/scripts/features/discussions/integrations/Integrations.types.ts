/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser, IUserFragment } from "@library/@types/api/users";
import { IconType } from "@vanilla/icons";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";

export interface IAttachmentIntegration {
    attachmentType: string;
    label: string;
    recordTypes: string[];
    submitButton: string;
    title: string;
    externalIDLabel: string;
    logoIcon: IconType;
}

export type IAttachmentIntegrationCatalog = Record<string, IAttachmentIntegration>;

export interface IAttachment {
    attachmentID: RecordID;
    attachmentType: IAttachmentIntegration["attachmentType"];
    recordType: string;
    recordID: string;
    foreignID?: RecordID;
    foreignUserID?: IUser["userID"];
    sourceID?: RecordID;
    sourceUrl?: string;
    dateInserted?: string;
    dateUpdated?: string;
    updateUser?: IUserFragment;
    insertUser?: IUserFragment;
    state?: string;
    status?: string;
    metadata: Array<{
        labelCode: string;
        value?: string | number;
    }>;
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
