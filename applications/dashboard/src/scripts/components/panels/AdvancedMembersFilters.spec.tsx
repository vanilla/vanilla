/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, act, cleanup, screen, fireEvent } from "@testing-library/react";
import AdvancedMembersFilters from "@dashboard/components/panels/AdvancedMembersFilters";
import { RenderResult } from "@testing-library/react";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import {
    ProfileField,
    ProfileFieldFormType,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { useCombinedMemberSearchSchema } from "@dashboard/components/panels/MembersSearchFilterPanel";

jest.setTimeout(20000);

const MockAdvancedMembersFilters = (props: Omit<React.ComponentProps<typeof AdvancedMembersFilters>, "schema">) => {
    const schema = useCombinedMemberSearchSchema();

    return <AdvancedMembersFilters {...props} schema={schema} />;
};

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

    const mockOnSubmit = jest.fn();

    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        profileFields: mockFields,
    });

    let result: RenderResult;
    beforeEach(async () => {
        await act(async () => {
            result = render(
                <PermissionsFixtures.SpecificPermissions permissions={["personalInfo.view"]}>
                    <MockProfileFieldsProvider>
                        <MockAdvancedMembersFilters onSubmit={mockOnSubmit} />
                    </MockProfileFieldsProvider>
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
            const textFields = await screen.findAllByRole("textbox");

            await act(async () => {
                fireEvent.change(textFields[0], { target: { value: "123" } });
                fireEvent.change(textFields[1], { target: { value: "456" } });
            });

            const clearAllButton = await result.findByRole("button", { name: "Clear All" });
            expect(clearAllButton).toBeInTheDocument();

            await act(async () => {
                fireEvent.click(clearAllButton);
            });

            expect(textFields[0]).toHaveValue("");
            expect(textFields[1]).toHaveValue("");
        });

        it("Submitting the form calls the onSubmit function, and closes the modal", async () => {
            await act(async () => {
                fireEvent.submit(result.getByRole("dialog").querySelector("form")!);
            });

            expect(result.queryByRole("dialog")).not.toBeInTheDocument();
            expect(mockOnSubmit).toHaveBeenCalledTimes(1);
        });
    });
});
