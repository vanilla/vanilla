/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getFormattedValue, visibilityIcon } from "@library/features/discussions/forms/PostSettingsUtils";
import { PostField } from "@dashboard/postTypes/postType.types";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { render } from "@testing-library/react";

// Mock the i18n functions to control test behavior
vi.mock("@vanilla/i18n", () => ({
    t: (str: string) => str,
    getJSLocaleKey: () => "en-US",
    formatList: (arr: string[]) => arr.join(", "),
}));

describe("PostSettingsUtils", () => {
    describe("getFormattedValue", () => {
        // Test for boolean type
        it("formats boolean values correctly", () => {
            const field: PostField = {
                dataType: CreatableFieldDataType.BOOLEAN,
                postFieldID: "1",
                label: "Active",
                description: "",
                postTypeIDs: [],
                formType: CreatableFieldFormType.TEXT,
                visibility: CreatableFieldVisibility.PUBLIC,
                isRequired: false,
                isActive: false,
                sort: 0,
                dateInserted: "",
                dateUpdated: "",
                insertUserID: "",
                updateUserID: "",
            };

            expect(getFormattedValue(field, "true")).toBe("Yes");
            expect(getFormattedValue(field, "false")).toBe("No");
            expect(getFormattedValue(field, "")).toBe("No");
            expect(getFormattedValue(field, undefined)).toBe("This field is empty in this post");
        });

        // Test for date type
        it("formats date values correctly", () => {
            const field: PostField = {
                dataType: CreatableFieldDataType.DATE,
                postFieldID: "2",
                label: "Created Date",
                postTypeIDs: [],
                description: "",
                formType: CreatableFieldFormType.TEXT,
                visibility: CreatableFieldVisibility.PUBLIC,
                isRequired: false,
                isActive: false,
                sort: 0,
                dateInserted: "",
                dateUpdated: "",
                insertUserID: "",
                updateUserID: "",
            };

            // Using a fixed date to make the test deterministic
            const mockDate = "2025-05-27T00:00:00.000Z";

            // Since we're mocking getJSLocaleKey to return "en-US" and controlling the date format,
            expect(getFormattedValue(field, mockDate)).toMatch(/May 27, 2025/);
            expect(getFormattedValue(field, "invalid-date")).toBe("This field is empty in this post");
            expect(getFormattedValue(field, undefined)).toBe("This field is empty in this post");
        });

        // Test for string[] type
        it("formats string array values correctly", () => {
            const field: PostField = {
                dataType: CreatableFieldDataType.STRING_MUL,
                postFieldID: "3",
                label: "Tags",
                postTypeIDs: [],
                description: "",
                formType: CreatableFieldFormType.TEXT,
                visibility: CreatableFieldVisibility.PUBLIC,
                isRequired: false,
                isActive: false,
                sort: 0,
                dateInserted: "",
                dateUpdated: "",
                insertUserID: "",
                updateUserID: "",
            };

            const mockTags = ["tag1", "tag2", "tag3"];

            // With our mock implementation, this should join with commas
            expect(getFormattedValue(field, mockTags)).toBe("tag1, tag2, tag3");
            expect(getFormattedValue(field, undefined)).toBe("This field is empty in this post");
        });

        // Test for default (string) type
        it("formats default (string) values correctly", () => {
            const field: PostField = {
                dataType: CreatableFieldDataType.TEXT,
                postFieldID: "4",
                label: "Title",
                postTypeIDs: [],
                description: "",
                formType: CreatableFieldFormType.TEXT,
                visibility: CreatableFieldVisibility.PUBLIC,
                isRequired: false,
                isActive: false,
                sort: 0,
                dateInserted: "",
                dateUpdated: "",
                insertUserID: "",
                updateUserID: "",
            };

            expect(getFormattedValue(field, "Sample Title")).toBe("Sample Title");
            expect(getFormattedValue(field, "")).toBe("This field is empty in this post");
            expect(getFormattedValue(field, undefined)).toBe("This field is empty in this post");
        });
    });

    describe("visibilityIcon", () => {
        it("returns the correct icon component for internal visibility", () => {
            const { container } = render(visibilityIcon("internal" as CreatableFieldVisibility));
            expect(container.innerHTML).toContain("visibility-internal");
        });

        it("returns the correct icon component for private visibility", () => {
            const { container } = render(visibilityIcon("private" as CreatableFieldVisibility));
            expect(container.innerHTML).toContain("visibility-private");
        });

        it("returns empty fragment for other visibility values", () => {
            const { container } = render(visibilityIcon("public" as CreatableFieldVisibility));
            expect(container.innerHTML).toBe("");
        });
    });
});
