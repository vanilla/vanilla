/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { slugify } from "@vanilla/utils";

export class PostTypeFixture {
    public static mockPostType: PostType = {
        postTypeID: "mock-post-type",
        name: "Mock Post Type",
        parentPostTypeID: "discussion",
        isOriginal: false,
        isActive: false,
        isDeleted: false,
        postButtonLabel: "Mock Post Type Button Label",
        postHelperText: "Mock Post Type Helper Text",
        roleIDs: [],
        countCategories: 30,
        dateInserted: "2024-11-01 16:47:08",
        dateUpdated: "2024-11-01 18:16:48",
        insertUserID: 2,
        updateUserID: 2,
        categoryIDs: [],
        postFields: this.getPostFields(),
        postFieldIDs: this.getPostFields().map((postField) => postField.postFieldID),
    };

    public static mockPostField = {
        postFieldID: "mock-post-field-id",
        postTypeIDs: ["mock-post-type-id"],
        label: "Mock Post Field Label",
        description: "Mock Post Field Description",
        dataType: CreatableFieldDataType.TEXT,
        formType: CreatableFieldFormType.TEXT,
        visibility: CreatableFieldVisibility.PUBLIC,
        isRequired: true,
        isActive: false,
        sort: 1,
    };

    public static getPostFields(numberOfPostFields = 5, overrides?: Partial<PostField>): PostField[] {
        return Array.from({ length: numberOfPostFields }, (_, index) => {
            const label = `Mock Post Field ${index + 1}`;
            const postFieldID = slugify(label);
            return {
                ...this.mockPostField,
                label,
                postFieldID,
                ...overrides,
            } as PostField;
        });
    }

    public static getPostTypes(numberOfPostTypes = 5, overrides?: Partial<PostType>): PostType[] {
        return Array.from({ length: numberOfPostTypes }, (_, index) => {
            const name = `Mock Post Type ${index + 1}`;
            const postTypeID = slugify(name);
            return {
                ...this.mockPostType,
                name,
                postTypeID,
                ...overrides,
            } as PostType;
        });
    }
}
