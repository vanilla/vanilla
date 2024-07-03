/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser, IUserFragment } from "@library/@types/api/users";
import { IAttachmentLayoutProps } from "@library/features/discussions/integrations/components/AttachmentLayout";
import { IconType } from "@vanilla/icons";
import { IFieldError, JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import { FormikHelpers } from "formik";

interface IReadableAttachmentIntegration {
    attachmentType: string;
    title: string;
    externalIDLabel: string;
    logoIcon: IconType;
}

export interface IWriteableAttachmentIntegration extends IReadableAttachmentIntegration {
    name: string;
    label: string;
    recordTypes: string[];
    submitButton: string;
}
export type IAttachmentIntegration = IReadableAttachmentIntegration | IWriteableAttachmentIntegration;

export function isWriteableAttachmentIntegration(
    integration: IAttachmentIntegration,
): integration is IWriteableAttachmentIntegration {
    return (
        "name" in integration && "label" in integration && "recordTypes" in integration && "submitButton" in integration
    );
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
        value: string | number;
        url?: string;
        format?: "date-time";
    }>;
}

export interface IGetAttachmentSchemaParams {
    attachmentType: string;
    recordType: string;
    recordID: RecordID;
    projectID?: string;
    issueTypeID?: string;
}

export interface IPostAttachmentParams {
    // a loose type, since each integration will have its own schema
    [key: string]: any;
}

export interface IRefreshAttachmentsParams {
    attachmentIDs: Array<IAttachment["attachmentID"]>;
    onlyStale?: boolean;
}

export interface IIntegrationsApi {
    getIntegrationsCatalog: () => Promise<IAttachmentIntegrationCatalog>;
    getAttachmentSchema: (params: IGetAttachmentSchemaParams) => Promise<JsonSchema>;
    postAttachment: (params: IPostAttachmentParams) => Promise<IAttachment>;
    refreshAttachments: (params: IRefreshAttachmentsParams) => Promise<IAttachment[]>;
}

export interface ICustomIntegrationContext {
    transformLayout?: (props: IAttachmentLayoutProps) => IAttachmentLayoutProps & Record<string, any>;
    beforeSubmit?: (values?: any) => any;
    CustomIntegrationForm?: React.ComponentType<{
        values?: any;
        schema?: JsonSchema;
        onChange?: FormikHelpers<IPostAttachmentParams>["setValues"];
        fieldErrors?: Record<string, IFieldError[]>;
    }>;
}

export type CustomIntegrationContext = () => ICustomIntegrationContext;
