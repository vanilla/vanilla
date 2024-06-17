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
    IAttachmentIntegration,
    IAttachmentIntegrationCatalog,
    ICustomIntegrationContext,
    IIntegrationsApi,
    IPostAttachmentParams,
} from "@library/features/discussions/integrations/Integrations.types";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQuery } from "@tanstack/react-query";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import { PropsWithChildren, createContext, useContext } from "react";
import { lookupCustomIntegrationsContext } from "@library/features/discussions/integrations/Integrations.registry";

export interface IAttachmentIntegrationsApiContextValue {
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
    refreshStaleAttachments: (attachmentIDs: Array<IAttachment["attachmentID"]>) => Promise<void>;
}

export const AttachmentIntegrationsContext = createContext<IAttachmentIntegrationsContextValue>({
    integrations: [],
    refreshStaleAttachments: async (_attachmentIDs) => ({} as any),
});

export function useAttachmentIntegrations() {
    return useContext(AttachmentIntegrationsContext).integrations;
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

    const refreshStaleAttachments = useMutation({
        mutationFn: async (attachmentIDs: Array<IAttachment["attachmentID"]>) => {
            await api.refreshAttachments({ attachmentIDs, onlyStale: true });
        },
    });

    return (
        <AttachmentIntegrationsContext.Provider
            value={{
                ...{
                    integrations: Object.values(integrationsValue ?? {}),
                    refreshStaleAttachments: refreshStaleAttachments.mutateAsync,
                },
            }}
        >
            {props.children}
        </AttachmentIntegrationsContext.Provider>
    );
}

export interface IIntegrationContextValue {
    getSchema: () => Promise<JsonSchema>;
    schema: ILoadable<JsonSchema>;
    postAttachment: (values: IPostAttachmentParams) => Promise<IAttachment>;
    label: IAttachmentIntegration["label"];
    submitButton: IAttachmentIntegration["submitButton"];
    title: IAttachmentIntegration["title"];
    externalIDLabel: IAttachmentIntegration["externalIDLabel"];
    logoIcon: IAttachmentIntegration["logoIcon"];
    name: IAttachmentIntegration["name"];

    // context customizations
    transformLayout?: ICustomIntegrationContext["transformLayout"];
    beforeSubmit?: ICustomIntegrationContext["beforeSubmit"];
    CustomIntegrationForm?: ICustomIntegrationContext["CustomIntegrationForm"];
}

export const IntegrationContext = createContext<IIntegrationContextValue>({
    getSchema: async () => ({} as any),
    schema: { status: LoadStatus.PENDING },
    postAttachment: async () => ({} as any),
    label: "",
    submitButton: "",
    title: "",
    externalIDLabel: "",
    logoIcon: "meta-external",
    name: "",
});

export function useIntegrationContext() {
    return useContext(IntegrationContext);
}

export function IntegrationContextProvider(
    props: PropsWithChildren<{
        attachmentType: string;
        recordType: string;
        recordID: RecordID;
    }>,
) {
    const api = useAttachmentIntegrationsApi();
    const integrations = useAttachmentIntegrations();
    const { children, attachmentType, recordType, recordID } = props;

    const integration = integrations.find((i) => i.attachmentType === attachmentType);
    const {
        label = "",
        submitButton = "",
        title = "",
        externalIDLabel = "",
        logoIcon = "meta-external",
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
        return null;
    }

    return (
        <IntegrationContext.Provider
            value={{
                ...{
                    name: integration.name,
                    label,
                    submitButton,
                    title,
                    externalIDLabel,
                    logoIcon,
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
        </IntegrationContext.Provider>
    );
}
