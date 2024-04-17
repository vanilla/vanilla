/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    AttachmentIntegrationsApiContextProvider,
    AttachmentIntegrationsContextProvider,
    INTEGRATIONS_META_KEY,
    IntegrationContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import {
    FAKE_API,
    FAKE_INTEGRATION,
    FAKE_INTEGRATIONS_CATALOG,
    FAKE_INTEGRATION_SCHEMAS,
} from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { RenderResult, act, fireEvent, render, within } from "@testing-library/react";
import { IFormControl } from "@vanilla/json-schema-forms";
import { IntegrationButtonAndModal } from "./Integrations";
import { IIntegrationsApi } from "./Integrations.types";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const mockApi: IIntegrationsApi = {
    getIntegrationsCatalog: jest.fn(FAKE_API.getIntegrationsCatalog),
    getAttachmentSchema: jest.fn(FAKE_API.getAttachmentSchema),
    postAttachment: jest.fn(FAKE_API.postAttachment),
    refreshAttachments: jest.fn(FAKE_API.refreshAttachments),
};

beforeEach(() => {
    setMeta(INTEGRATIONS_META_KEY, undefined);
});

describe("IntegrationButtonAndModal", () => {
    let result: RenderResult;

    let buttonPromise: Promise<HTMLElement>;

    beforeEach(async () => {
        await act(async () => {
            result = render(
                <QueryClientProvider client={queryClient}>
                    <AttachmentIntegrationsApiContextProvider api={mockApi}>
                        <AttachmentIntegrationsContextProvider integrations={FAKE_INTEGRATIONS_CATALOG}>
                            <IntegrationContextProvider
                                {...{
                                    attachmentType: FAKE_INTEGRATION["attachmentType"],
                                    recordType: "discussion",
                                    recordID: 1,
                                }}
                            >
                                <IntegrationButtonAndModal />
                            </IntegrationContextProvider>
                        </AttachmentIntegrationsContextProvider>
                    </AttachmentIntegrationsApiContextProvider>
                </QueryClientProvider>,
            );
        });
        buttonPromise = result.findByText(FAKE_INTEGRATION.label);
    });

    it("Renders a button with the integration's `label` property", async () => {
        const button = await buttonPromise;
        expect(button).toBeInTheDocument();
    });

    describe("When the button is clicked", () => {
        let dialogPromise: Promise<HTMLElement>;

        beforeEach(async () => {
            const button = await buttonPromise;

            await act(async () => {
                fireEvent.click(button);
            });

            dialogPromise = result.findByRole("dialog");
        });
        it("Opens the modal", async () => {
            const modal = await dialogPromise;
            expect(modal).toBeInTheDocument();
        });

        it("The modal contains a form", async () => {
            const form = await within(await dialogPromise).findByRole<HTMLFormElement>("form");
            expect(form).toBeInTheDocument();
        });

        describe("Attachment form", () => {
            it("The form contains fields corresponding to the schema. The fields have the default values from the schema", async () => {
                const form = await within(await dialogPromise).findByRole<HTMLFormElement>("form");

                const schema = FAKE_INTEGRATION_SCHEMAS[FAKE_INTEGRATION.attachmentType];

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
                const form = within(await dialogPromise).getByRole("form");
                const submitButton = await within(form).findByRole<HTMLButtonElement>("button", {
                    name: FAKE_INTEGRATION.submitButton,
                });
                expect(submitButton).toBeInTheDocument();
                expect(submitButton.type).toBe("submit");
            });

            describe("Submitting the form", () => {
                it("Calls the API's `postAttachment` method", async () => {
                    const form = within(await dialogPromise).getByRole("form");
                    const submitButton = await within(form).findByRole<HTMLButtonElement>("button", {
                        name: FAKE_INTEGRATION.submitButton,
                    });
                    await act(async () => {
                        fireEvent.click(submitButton);
                    });
                    expect(mockApi.postAttachment).toHaveBeenCalledTimes(1);
                });
            });
        });
    });
});
