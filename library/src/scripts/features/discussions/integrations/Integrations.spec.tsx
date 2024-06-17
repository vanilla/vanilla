/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    AttachmentIntegrationsApiContextProvider,
    AttachmentIntegrationsContextProvider,
    INTEGRATIONS_META_KEY,
    WriteableIntegrationContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import {
    FAKE_API,
    FAKE_WRITEABLE_INTEGRATION,
    FAKE_INTEGRATIONS_CATALOG,
    FAKE_INTEGRATION_SCHEMAS,
} from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { RenderResult, act, fireEvent, render, within, screen } from "@testing-library/react";
import { IFormControl } from "@vanilla/json-schema-forms";
import { IntegrationButtonAndModal } from "./Integrations";
import { IIntegrationsApi } from "./Integrations.types";
import { vitest } from "vitest";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const mockApi: IIntegrationsApi = {
    getIntegrationsCatalog: vitest.fn(FAKE_API.getIntegrationsCatalog),
    getAttachmentSchema: vitest.fn(FAKE_API.getAttachmentSchema),
    postAttachment: vitest.fn(FAKE_API.postAttachment),
    refreshAttachments: vitest.fn(FAKE_API.refreshAttachments),
};

beforeEach(() => {
    setMeta(INTEGRATIONS_META_KEY, undefined);
});

describe("IntegrationButtonAndModal", () => {
    let button: HTMLElement;

    beforeEach(async () => {
        await act(async () => {
            render(
                <QueryClientProvider client={queryClient}>
                    <AttachmentIntegrationsApiContextProvider api={mockApi}>
                        <AttachmentIntegrationsContextProvider integrations={FAKE_INTEGRATIONS_CATALOG}>
                            <WriteableIntegrationContextProvider
                                {...{
                                    attachmentType: FAKE_WRITEABLE_INTEGRATION["attachmentType"],
                                    recordType: "discussion",
                                    recordID: 1,
                                }}
                            >
                                <IntegrationButtonAndModal />
                            </WriteableIntegrationContextProvider>
                        </AttachmentIntegrationsContextProvider>
                    </AttachmentIntegrationsApiContextProvider>
                </QueryClientProvider>,
            );
        });
        button = await screen.findByText(FAKE_WRITEABLE_INTEGRATION.label);
    });

    it("Renders a button with the integration's `label` property", async () => {
        expect(button).toBeInTheDocument();
    });

    describe("When the button is clicked", () => {
        let modal: HTMLElement;

        beforeEach(async () => {
            expect(button).toBeInTheDocument();
            fireEvent.click(button);
            await vi.dynamicImportSettled();
            modal = await screen.findByRole("dialog");
        });
        it("Opens the modal", async () => {
            expect(modal).toBeInTheDocument();
        });

        it("The modal contains a form", async () => {
            const form = await within(modal).findByRole<HTMLFormElement>("form");
            expect(form).toBeInTheDocument();
        });

        describe("Attachment form", () => {
            it("The form contains fields corresponding to the schema. The fields have the default values from the schema", async () => {
                const form = await within(modal).findByRole<HTMLFormElement>("form");

                const schema = FAKE_INTEGRATION_SCHEMAS[FAKE_WRITEABLE_INTEGRATION.attachmentType];

                expect.assertions(Object.keys(schema.properties).length * 2);

                for (const [key, value] of Object.entries(schema.properties)) {
                    const input = within(form).getByLabelText((value["x-control"] as IFormControl).label!, {
                        exact: false,
                    });
                    expect(input).toBeInTheDocument();
                    expect(input).toHaveValue(schema.properties[key].default);
                }
            });

            it("The form contains a submit button with the label from the integration", async () => {
                const form = within(modal).getByRole("form");
                const submitButton = await within(form).findByRole<HTMLButtonElement>("button", {
                    name: FAKE_WRITEABLE_INTEGRATION.submitButton,
                });
                expect(submitButton).toBeInTheDocument();
                expect(submitButton.type).toBe("submit");
            });

            describe("Submitting the form", () => {
                it("Calls the API's `postAttachment` method", async () => {
                    const form = within(modal).getByRole("form");
                    await act(async () => {
                        fireEvent.submit(form);
                    });
                    expect(mockApi.postAttachment).toHaveBeenCalledTimes(1);
                });
            });
        });
    });
});
