/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { setMeta } from "@library/utility/appUtils";
import {
    AttachmentIntegrationsApiContextProvider,
    AttachmentIntegrationsContextProvider,
    IIntegrationContextValue,
    INTEGRATIONS_META_KEY,
    IntegrationContextProvider,
    useAttachmentIntegrations,
    useIntegrationContext,
} from "@library/features/discussions/integrations/Integrations.context";
import {
    FAKE_INTEGRATION,
    FAKE_INTEGRATIONS_CATALOG,
    createMockApi,
} from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook, RenderResult, act } from "@testing-library/react-hooks";
import { IAttachmentIntegration } from "./Integrations.types";
import { PropsWithChildren } from "react";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const mockApi = createMockApi();

export function TestWrapper({ children }: PropsWithChildren<{}>) {
    return (
        <QueryClientProvider client={queryClient}>
            <AttachmentIntegrationsApiContextProvider api={mockApi}>
                <AttachmentIntegrationsContextProvider>{children}</AttachmentIntegrationsContextProvider>
            </AttachmentIntegrationsApiContextProvider>
        </QueryClientProvider>
    );
}

beforeEach(() => {
    setMeta(INTEGRATIONS_META_KEY, undefined);
});

describe("AttachmentIntegrationsContext", () => {
    let result: RenderResult<IAttachmentIntegration[]>;

    describe("With integrations in the meta", () => {
        beforeEach(() => {
            setMeta(INTEGRATIONS_META_KEY, FAKE_INTEGRATIONS_CATALOG);

            act(() => {
                const renderHookResult = renderHook(() => useAttachmentIntegrations(), {
                    wrapper: TestWrapper,
                });
                result = renderHookResult.result;
            });
        });

        it("AttachmentIntegrationsContextProvider does not call the API to retrieve integrations catalog", () => {
            expect(mockApi.getIntegrationsCatalog).not.toHaveBeenCalled();
        });

        it("useAttachmentIntegrations provides the integrations from the meta", () => {
            expect(result.current).toEqual(Object.values(FAKE_INTEGRATIONS_CATALOG));
        });
    });

    describe("Without integrations in the meta", () => {
        beforeEach(() => {
            setMeta(INTEGRATIONS_META_KEY, undefined);
            act(() => {
                const renderHookResult = renderHook(() => useAttachmentIntegrations(), {
                    wrapper: TestWrapper,
                });
                result = renderHookResult.result;
            });
        });

        it("AttachmentIntegrationsContextProvider calls the integrations API to get the integrations", () => {
            expect(mockApi.getIntegrationsCatalog).toHaveBeenCalledTimes(1);
        });

        it("useAttachmentIntegrations provides the integrations retrieved from the API", () => {
            expect(result.current).toEqual(Object.values(FAKE_INTEGRATIONS_CATALOG));
        });
    });
});

describe("IntegrationContext", () => {
    let result: RenderResult<IIntegrationContextValue>;

    describe("IntegrationContext", () => {
        beforeEach(async () => {
            setMeta(INTEGRATIONS_META_KEY, FAKE_INTEGRATIONS_CATALOG);

            await act(async () => {
                const renderHookResult = renderHook(() => useIntegrationContext(), {
                    wrapper: function Wrapper(props: React.ComponentProps<typeof IntegrationContextProvider>) {
                        return (
                            <TestWrapper>
                                <IntegrationContextProvider
                                    {...{
                                        attachmentType: FAKE_INTEGRATION["attachmentType"],
                                        recordType: "discussion",
                                        recordID: 1,
                                    }}
                                >
                                    {props.children}
                                </IntegrationContextProvider>
                            </TestWrapper>
                        );
                    },
                });
                result = renderHookResult.result;
            });
        });

        it("does not initially call the API to retrieve the schema", () => {
            expect(mockApi.getAttachmentSchema).not.toHaveBeenCalled();
        });

        it("calls the API to retrieve the schema, only when getSchema is invoked", async () => {
            await act(async () => {
                await result.current.getSchema();
            });
            expect(mockApi.getAttachmentSchema).toHaveBeenCalledTimes(1);
        });
    });
});
