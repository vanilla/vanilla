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
    IIntegrationsApi,
    IPostAttachmentParams,
} from "@library/features/discussions/integrations/Integrations.types";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQuery } from "@tanstack/react-query";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import { PropsWithChildren, createContext, useContext } from "react";

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
}

const AttachmentIntegrationsContext = createContext<IAttachmentIntegrationsContextValue>({
    integrations: [],
});

export function useAttachmentIntegrations() {
    return useContext(AttachmentIntegrationsContext).integrations;
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

    const availableIntegrations = integrationsQuery.isSuccess ? integrationsQuery.data! : {};

    return (
        <AttachmentIntegrationsContext.Provider
            value={{
                ...{
                    integrations: Object.values(availableIntegrations),
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

    const schemaQuery = useQuery<unknown, IApiError, JsonSchema>({
        queryFn: async () => await api.getAttachmentSchema({ attachmentType, recordType, recordID }),
        queryKey: ["attachmentSchema", attachmentType, recordType, recordID],
        enabled: false,
    });

    const schema = queryResultToILoadable(schemaQuery);

    const postAttachment = useMutation({
        mutationFn: async (values: IPostAttachmentParams) => {
            const response = await api.postAttachment(values);
            return response;
        },
    });
    return (
        <IntegrationContext.Provider
            value={{
                ...{
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
                },
            }}
        >
            {children}
        </IntegrationContext.Provider>
    );
}
