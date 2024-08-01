/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import { queryResultToILoadable } from "@library/ReactQueryUtils";
import { IntegrationsApi } from "@library/features/discussions/integrations/Integrations.api";
import {
    IAttachment,
    IAttachmentIntegrationCatalog,
    ICustomIntegrationContext,
    IIntegrationsApi,
    IPostAttachmentParams,
    IAttachmentIntegration,
    isWriteableAttachmentIntegration,
} from "@library/features/discussions/integrations/Integrations.types";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQuery } from "@tanstack/react-query";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID, logDebug } from "@vanilla/utils";
import { PropsWithChildren, createContext, useContext } from "react";
import { lookupCustomIntegrationsContext } from "@library/features/discussions/integrations/Integrations.registry";

interface IAttachmentIntegrationsApiContextValue {
    api: IIntegrationsApi;
}

const AttachmentIntegrationsApiContext = createContext<IAttachmentIntegrationsApiContextValue>({
    api: IntegrationsApi,
});

export function useAttachmentIntegrationsApi() {
    return useContext(AttachmentIntegrationsApiContext).api;
}

export function AttachmentIntegrationsApiContextProvider({
    api = IntegrationsApi,
    children,
}: PropsWithChildren<{
    api?: IIntegrationsApi;
}>) {
    return (
        <AttachmentIntegrationsApiContext.Provider
            value={{
                ...{
                    api,
                },
            }}
        >
            {children}
        </AttachmentIntegrationsApiContext.Provider>
    );
}

export const INTEGRATIONS_META_KEY = "externalAttachments";

function getIntegrationsFromMeta(): IAttachmentIntegrationCatalog | undefined {
    return getMeta(INTEGRATIONS_META_KEY, undefined);
}
interface IAttachmentIntegrationsContextValue {
    integrations: IAttachmentIntegration[];
    writeableIntegrations: IAttachmentIntegration[];
    refreshStaleAttachments: (attachments: IAttachment[]) => Promise<void>;
}

export const AttachmentIntegrationsContext = createContext<IAttachmentIntegrationsContextValue>({
    integrations: [],
    writeableIntegrations: [],
    refreshStaleAttachments: async (_attachments) => ({} as any),
});

function useAttachmentIntegrations() {
    return useContext(AttachmentIntegrationsContext).integrations;
}

export function useWriteableAttachmentIntegrations() {
    return useContext(AttachmentIntegrationsContext).writeableIntegrations;
}

export function useRefreshStaleAttachments() {
    return useContext(AttachmentIntegrationsContext).refreshStaleAttachments;
}

export function AttachmentIntegrationsContextProvider(
    props: PropsWithChildren<{
        integrations?: IAttachmentIntegrationCatalog;
    }>,
) {
    const api = useAttachmentIntegrationsApi();

    const initialIntegrations = props.integrations ?? getIntegrationsFromMeta();

    const integrationsQuery = useQuery({
        queryFn: async () => await api.getIntegrationsCatalog(),
        queryKey: ["integrations"],
        initialData: initialIntegrations,
        enabled: initialIntegrations === undefined,
    });

    const integrationsValue = integrationsQuery.data ?? initialIntegrations;

    const integrations = Object.values(integrationsValue ?? {});
    const writeableIntegrations = integrationsValue
        ? Object.values(integrationsValue).filter((integration) => isWriteableAttachmentIntegration(integration))
        : [];

    const refreshStaleAttachments = useMutation({
        mutationFn: async (attachments: IAttachment[]) => {
            const attachmentIDs = attachments.map(({ attachmentID }) => attachmentID);

            if (attachmentIDs.length > 0) {
                await api.refreshAttachments({ attachmentIDs, onlyStale: true });
            }
        },
    });

    return (
        <AttachmentIntegrationsContext.Provider
            value={{
                ...{
                    integrations,
                    writeableIntegrations,
                    refreshStaleAttachments: refreshStaleAttachments.mutateAsync,
                },
            }}
        >
            {props.children}
        </AttachmentIntegrationsContext.Provider>
    );
}

interface IReadableIntegrationContextValue {
    title: IAttachmentIntegration["title"];
    externalIDLabel: IAttachmentIntegration["externalIDLabel"];
    logoIcon: IAttachmentIntegration["logoIcon"];
    attachmentTypeIcon: IAttachmentIntegration["attachmentTypeIcon"];
}

const ReadableIntegrationContext = createContext<IReadableIntegrationContextValue>({
    title: "",
    externalIDLabel: "",
    logoIcon: "meta-external-compact",
    attachmentTypeIcon: undefined,
});

export function useReadableIntegrationContext() {
    return useContext(ReadableIntegrationContext);
}

export function ReadableIntegrationContextProvider(
    props: PropsWithChildren<{
        attachmentType: string;
    }>,
) {
    const integrations = useAttachmentIntegrations();
    const { children, attachmentType } = props;

    const integration = integrations.find((i) => i.attachmentType === attachmentType);
    if (!integration) {
        logDebug(
            `No integration found for attachment type: ${attachmentType}. Are you missing the <AttachmentIntegrationsContextProvider/>?`,
        );
        return null;
    }

    const { title, externalIDLabel, logoIcon = "meta-external-compact", attachmentTypeIcon } = integration ?? {};

    return (
        <ReadableIntegrationContext.Provider
            value={{
                title,
                externalIDLabel,
                logoIcon,
                attachmentTypeIcon,
            }}
        >
            {children}
        </ReadableIntegrationContext.Provider>
    );
}

interface IWriteableIntegrationContextValue {
    getSchema: () => Promise<JsonSchema>;
    schema: ILoadable<JsonSchema>;
    postAttachment: (values: IPostAttachmentParams) => Promise<IAttachment>;

    label: IAttachmentIntegration["label"];
    submitButton: IAttachmentIntegration["submitButton"];
    name: IAttachmentIntegration["name"];
    postSuccessMessage?: IAttachmentIntegration["postSuccessMessage"];

    // context customizations
    transformLayout?: ICustomIntegrationContext["transformLayout"];
    beforeSubmit?: ICustomIntegrationContext["beforeSubmit"];
    CustomIntegrationForm?: ICustomIntegrationContext["CustomIntegrationForm"];
}

const WriteableIntegrationContext = createContext<IWriteableIntegrationContextValue>({
    getSchema: async () => ({} as any),
    schema: { status: LoadStatus.PENDING },
    postAttachment: async () => ({} as any),
    label: "",
    submitButton: "",
    name: "",
    postSuccessMessage: undefined,
});

export function useWriteableIntegrationContext() {
    return useContext(WriteableIntegrationContext);
}

export function WriteableIntegrationContextProvider(
    props: PropsWithChildren<{
        attachmentType: string;
        recordType: string;
        recordID: RecordID;
    }>,
) {
    const api = useAttachmentIntegrationsApi();
    const writeableIntegrations = useWriteableAttachmentIntegrations();
    const { children, attachmentType, recordType, recordID } = props;

    const integration = writeableIntegrations.find((i) => i.attachmentType === attachmentType);
    const {
        label = "",
        submitButton = "",
        title = "",
        externalIDLabel = "",
        logoIcon = "meta-external-compact",
        attachmentTypeIcon,
        postSuccessMessage,
    } = integration ?? {};

    const customContextQuery = useQuery({
        queryFn: async () => {
            const customContext = lookupCustomIntegrationsContext(attachmentType);
            if (customContext) {
                const contextFn = await customContext();
                return contextFn();
            }
            return null;
        },
        queryKey: [attachmentType],
    });

    const schemaQuery = useQuery<unknown, IApiError, JsonSchema>({
        retry: false,
        queryFn: async () => await api.getAttachmentSchema({ attachmentType, recordType, recordID }),
        queryKey: ["attachmentSchema", attachmentType, recordType, recordID],
        enabled: false,
    });

    let schema = queryResultToILoadable(schemaQuery);
    if (customContextQuery.isLoading) {
        schema = {
            status: LoadStatus.LOADING,
            data: undefined,
        };
    }

    const postAttachment = useMutation({
        mutationFn: async (values: IPostAttachmentParams) => {
            const response = await api.postAttachment(values);
            return response;
        },
    });

    if (!integration) {
        logDebug(
            `No integration found for attachment type: ${attachmentType}. Are you missing the <AttachmentIntegrationsContextProvider/>?`,
        );
        return null;
    }

    return (
        <WriteableIntegrationContext.Provider
            value={{
                ...{
                    name: integration.name,
                    label,
                    submitButton,
                    title,
                    externalIDLabel,
                    logoIcon,
                    attachmentTypeIcon,
                    postSuccessMessage,
                    schema,
                    getSchema: async () => {
                        const response = await schemaQuery.refetch();
                        return response.data!;
                    },
                    postAttachment: async (values) => {
                        const response = await postAttachment.mutateAsync(values);
                        return response;
                    },
                    transformLayout: customContextQuery?.data?.transformLayout,
                    beforeSubmit: customContextQuery?.data?.beforeSubmit,
                    CustomIntegrationForm: customContextQuery?.data?.CustomIntegrationForm,
                },
            }}
        >
            {children}
        </WriteableIntegrationContext.Provider>
    );
}
