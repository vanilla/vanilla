/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    INTEGRATIONS_META_KEY,
    WriteableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
    useWriteableIntegrationContext,
} from "@library/features/discussions/integrations/Integrations.context";
import {
    FAKE_WRITEABLE_INTEGRATION,
    FAKE_INTEGRATIONS_CATALOG,
    IntegrationsTestWrapper,
    mockApi,
} from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import { setMeta } from "@library/utility/appUtils";
import { RenderResult, act, renderHook } from "@testing-library/react-hooks";

beforeEach(() => {
    setMeta(INTEGRATIONS_META_KEY, undefined);
});

describe("WriteableAttachmentIntegrationsContext", () => {
    let result: RenderResult<ReturnType<typeof useWriteableAttachmentIntegrations>>;

    describe("With integrations in the meta", () => {
        beforeEach(() => {
            setMeta(INTEGRATIONS_META_KEY, FAKE_INTEGRATIONS_CATALOG);

            act(() => {
                const renderHookResult = renderHook(() => useWriteableAttachmentIntegrations(), {
                    wrapper: IntegrationsTestWrapper,
                });
                result = renderHookResult.result;
            });
        });

        it("does not call the API to retrieve integrations catalog", () => {
            expect(mockApi.getIntegrationsCatalog).not.toHaveBeenCalled();
        });

        it("provides the integrations from the meta", () => {
            expect(result.current).toEqual(Object.values(FAKE_INTEGRATIONS_CATALOG));
        });
    });

    describe("Without integrations in the meta", () => {
        beforeEach(() => {
            setMeta(INTEGRATIONS_META_KEY, undefined);
            act(() => {
                const renderHookResult = renderHook(() => useWriteableAttachmentIntegrations(), {
                    wrapper: IntegrationsTestWrapper,
                });
                result = renderHookResult.result;
            });
        });

        it("calls the integrations API to get the integrations", () => {
            expect(mockApi.getIntegrationsCatalog).toHaveBeenCalledTimes(1);
        });

        it("provides the integrations retrieved from the API", () => {
            expect(result.current).toEqual(Object.values(FAKE_INTEGRATIONS_CATALOG));
        });
    });
});

describe("WriteableIntegrationContext", () => {
    let result: RenderResult<ReturnType<typeof useWriteableIntegrationContext>>;

    beforeEach(async () => {
        setMeta(INTEGRATIONS_META_KEY, FAKE_INTEGRATIONS_CATALOG);

        await act(async () => {
            const renderHookResult = renderHook(() => useWriteableIntegrationContext(), {
                wrapper: function Wrapper(props: React.ComponentProps<typeof WriteableIntegrationContextProvider>) {
                    return (
                        <IntegrationsTestWrapper>
                            <WriteableIntegrationContextProvider
                                {...{
                                    attachmentType: FAKE_WRITEABLE_INTEGRATION["attachmentType"],
                                    recordType: "discussion",
                                    recordID: 1,
                                }}
                            >
                                {props.children}
                            </WriteableIntegrationContextProvider>
                        </IntegrationsTestWrapper>
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
