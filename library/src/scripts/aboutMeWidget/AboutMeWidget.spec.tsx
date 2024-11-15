/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render } from "@testing-library/react";
import { AboutMeWidget } from "@library/aboutMeWidget/AboutMeWidget";
import { ProfileField, ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { LoadStatus } from "@library/@types/api/core";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";

const lastField = { apiName: "lastField", label: "field-3", sort: 3 };
const firstField = { apiName: "firstField", label: "field-1", sort: 1 };
const secondField = { apiName: "secondField", label: "field-2", sort: 2 };

const mockFields: ProfileField[] = [
    ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TEXT, lastField),
    ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TEXT, firstField),
    ProfileFieldsFixtures.mockProfileField(ProfileFieldFormType.TEXT, secondField),
];

describe("AboutMeWidget", () => {
    it("displays the configured profile fields in the configured order", async () => {
        const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
            profileFields: mockFields,
            profileFieldsByUserID: {
                123: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        lastField: "last field value",
                        firstField: "first field value",
                        secondField: "second field value",
                    },
                },
            },
        });

        const { findAllByText } = render(
            <MockProfileFieldsProvider>
                <AboutMeWidget userID={123} />
            </MockProfileFieldsProvider>,
        );

        expect.assertions(4);

        const fields = await findAllByText(/field-[1-3]/);
        expect(fields).toHaveLength(3);
        expect(fields[0]).toHaveTextContent(firstField.label!);
        expect(fields[1]).toHaveTextContent(secondField.label!);
        expect(fields[2]).toHaveTextContent(lastField.label!);
    });
});
