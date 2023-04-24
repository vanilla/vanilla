/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, act, cleanup, fireEvent, waitFor, within } from "@testing-library/react";
import { RenderResult } from "@testing-library/react";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import {
    ProfileField,
    ProfileFieldFormType,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";
import { MembersSearchFilterPanel } from "@dashboard/components/panels/MembersSearchFilterPanel";
import { createReducer } from "@reduxjs/toolkit";
import { mockRolesState } from "@dashboard/components/panels/MembersSearchFilterPanel.spec";
import { registerMemberSearchDomain } from "@dashboard/components/panels/registerMemberSearchDomain";
import { mockAPI } from "@library/__tests__/utility";
import { SearchService } from "@library/search/SearchService";
import { ICustomControl } from "@vanilla/json-schema-forms";

jest.setTimeout(20000);

const mockAdapter = mockAPI();

describe("AdvancedMembersFilters", () => {
    // A mixture of public, internal and private fields: some configured to be searchable and others configure not to be searchable.
    const mockFields: ProfileField[] = [
        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TEXT_MULTILINE, {
            apiName: "public searchable text multiline",
            label: "public searchable text multiline",
            displayOptions: {
                search: true,
            },
            visibility: ProfileFieldVisibility.PUBLIC,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TEXT, {
            apiName: "public searchable text box",
            label: "public searchable text box",
            displayOptions: {
                search: true,
            },
            visibility: ProfileFieldVisibility.PUBLIC,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.CHECKBOX, {
            apiName: "public non-searchable checkbox",
            displayOptions: {
                search: false,
            },
            visibility: ProfileFieldVisibility.PUBLIC,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.CHECKBOX, {
            apiName: "internal searchable checkbox",
            displayOptions: {
                search: true,
            },
            visibility: ProfileFieldVisibility.INTERNAL,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TEXT, {
            apiName: "internal searchable text field",
            displayOptions: {
                search: true,
            },
            visibility: ProfileFieldVisibility.INTERNAL,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.CHECKBOX, {
            apiName: "private searchable checkbox field",
            displayOptions: {
                search: true,
            },
            visibility: ProfileFieldVisibility.PRIVATE,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.DROPDOWN, {
            apiName: "internal non-searchable text field",
            displayOptions: {
                search: false,
            },
            visibility: ProfileFieldVisibility.INTERNAL,
        }),

        ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.DROPDOWN, {
            apiName: "disabled public searchable text field",
            displayOptions: {
                search: true,
            },
            enabled: false,
            visibility: ProfileFieldVisibility.PUBLIC,
        }),
    ];

    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        profileFields: mockFields,
        extraReducers: {
            roles: createReducer(mockRolesState, () => {}),
        },
    });

    beforeAll(() => {
        registerMemberSearchDomain();

        const FakeInputComponent = function (props: { value: string; onChange: (value: string) => void }) {
            return <input type="textbox" value={props.value} onChange={(e) => props.onChange(e.target.value)}></input>;
        };

        SearchService.addFieldToDomainFilterSchema("members", "fakeExtensionField", {
            type: "string",
            "x-control": {
                inputType: "custom",
                label: "Fake label",
                component: FakeInputComponent,
            } as ICustomControl<typeof FakeInputComponent>,
        });
    });

    let result: RenderResult;

    beforeEach(async () => {
        await act(async () => {
            result = render(
                <PermissionsFixtures.SpecificPermissions permissions={["personalInfo.view"]}>
                    <SearchFormContextProvider>
                        <MockProfileFieldsProvider>
                            <MembersSearchFilterPanel />
                        </MockProfileFieldsProvider>
                    </SearchFormContextProvider>
                </PermissionsFixtures.SpecificPermissions>,
            );
        });
    });

    const searchableProfileFields = mockFields.filter(({ enabled, displayOptions: { search } }) => !!search && enabled);

    const nonSearchableProfileFields = mockFields.filter(({ displayOptions: { search } }) => !search);

    const disabledProfileFields = mockFields.filter(({ enabled }) => !enabled);

    afterAll(() => {
        cleanup();
    });

    it("The 'More Filters' button is rendered", async () => {
        expect(await result.findByLabelText("More Filters")).toBeInTheDocument();
    });

    it("Clicking the 'More Filters' button makes a modal visible. The modal contains a form.", async () => {
        await act(async () => {
            fireEvent.click(await result.findByLabelText("More Filters"));
        });
        const modal = await result.findByRole("dialog");
        expect(modal).toBeInTheDocument();
        expect(modal.querySelector("form")).toBeInTheDocument();
    });

    describe("Filters form", () => {
        beforeEach(
            async () =>
                await act(async () => {
                    fireEvent.click(await result.findByLabelText("More Filters"));
                }),
        );

        it("Contains the default filter inputs", async () => {
            const modal = await result.findByRole("dialog");
            expect(within(modal).queryByLabelText("Username")).toBeInTheDocument();
            expect(within(modal).queryByLabelText("Email")).toBeInTheDocument();
        });

        it("Contains the extension field", async () => {
            const modal = result.getByRole("dialog");
            const extensionField = await within(modal).findByLabelText("Fake label");
            expect(extensionField).toBeInTheDocument();
        });

        it("Has inputs for all the searchable profile fields, which the user is permitted to view.", () => {
            expect.hasAssertions();
            searchableProfileFields.forEach(({ label, visibility }) => {
                if ([ProfileFieldVisibility.PRIVATE, ProfileFieldVisibility.PUBLIC].includes(visibility)) {
                    expect(result.queryByText(label)).toBeInTheDocument();
                }
            });
        });

        it("Has inputs for all the searchable profile fields, which the user is permitted to view.", () => {
            expect.hasAssertions();
            searchableProfileFields.forEach(({ label, visibility }) => {
                if ([ProfileFieldVisibility.PRIVATE, ProfileFieldVisibility.PUBLIC].includes(visibility)) {
                    expect(result.queryByText(label)).toBeInTheDocument();
                }
            });
        });

        it("Does not contain inputs for searchable profile fields, if the user is not permitted to view them.", () => {
            expect.hasAssertions();
            searchableProfileFields.forEach(({ label, visibility }) => {
                if (visibility === ProfileFieldVisibility.INTERNAL) {
                    expect(result.queryByText(label)).not.toBeInTheDocument();
                }
            });
        });

        it("Does not have inputs for non-searchable profile fields", () => {
            expect.hasAssertions();
            nonSearchableProfileFields.forEach(({ label }) => {
                expect(result.queryByText(label)).not.toBeInTheDocument();
            });
        });

        it("Does not have inputs for disabled profile fields", () => {
            expect.hasAssertions();
            disabledProfileFields.forEach(async ({ label }) => {
                expect(result.queryByText(label)).not.toBeInTheDocument();
            });
        });

        it("Has a 'Clear All' button, which clears all form inputs", async () => {
            const modal = result.getByRole("dialog");

            const textFields = await within(modal).findAllByRole("textbox");

            await act(async () => {
                fireEvent.change(textFields[0], { target: { value: "123" } });
                fireEvent.change(textFields[1], { target: { value: "456" } });
            });

            const clearAllButton = await within(modal).findByRole("button", { name: "Clear All" });
            expect(clearAllButton).toBeInTheDocument();

            await act(async () => {
                fireEvent.click(clearAllButton);
            });

            expect(textFields[0]).toHaveValue("");
            expect(textFields[1]).toHaveValue("");
        });

        it("Submitting the form submits the search, and closes the modal", async () => {
            mockAdapter.onGet("/search").reply(200, []);

            await act(async () => {
                fireEvent.submit(result.getByRole("dialog").querySelector("form")!);
            });

            waitFor(() => {
                expect(result.queryByRole("dialog")).not.toBeInTheDocument();
                expect(mockAdapter.history.get.length).toEqual(1);
            });
            mockAdapter.reset();
        });
    });
});
